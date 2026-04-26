<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class UserAnonymizationService
{
    private const SCRUB_TOKEN = '[scrubbed]';

    private const SCRUB_FIELDS = ['email', 'name', 'phone', 'cpf'];

    /**
     * Pseudonymize a user: replace direct identifiers, cascade-delete
     * related PII tables, and scrub the audit trail while keeping the
     * user row intact so FK references hold.
     */
    public function anonymize(User $user): void
    {
        // Collect S3 paths before the transaction so the remote deletes
        // happen AFTER the DB work commits — never hold the transaction
        // open on a slow S3 call, and never orphan S3 objects if the DB
        // rolls back.
        $exportPaths = $user->dataExportRequests()
            ->whereNotNull('file_path')
            ->pluck('file_path')
            ->all();

        DB::transaction(function () use ($user): void {
            $this->scrubAuditLogs($user);
            $this->deleteIdentities($user);
            $this->scrubRatings($user);
            $this->revokeTokens($user);
            $this->deleteSessions($user);

            $user->forceFill([
                'name' => 'Usuário Removido',
                'email' => "removed-{$user->id}@deleted.local",
                'password' => bcrypt(Str::random(32)),
                'remember_token' => null,
                'email_verified_at' => null,
                'anonymized_at' => now(),
                'deleted_at' => $user->deleted_at ?? now(),
            ])->saveQuietly();
        });

        foreach ($exportPaths as $path) {
            try {
                Storage::disk('s3')->delete($path);
            } catch (Throwable $e) {
                // DB rows are already gone — log so ops can retry the remote
                // delete via lgpd:purge / an S3 lifecycle rule. Do not rethrow:
                // the anonymization itself must remain successful.
                Log::warning('LGPD anonymization: failed to delete export artifact from S3', [
                    'user_id' => $user->id,
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        AuditLog::record(
            event: AuditEvent::AccountAnonymized,
            auditable: $user,
        );
    }

    private function scrubAuditLogs(User $user): void
    {
        AuditLog::query()
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($inner) use ($user): void {
                        $inner->where('auditable_type', User::class)
                            ->where('auditable_id', $user->id);
                    });
            })
            ->lazyById()
            ->each(function (AuditLog $log) use ($user): void {
                $data = $log->event_data;
                if (! is_array($data)) {
                    return;
                }

                $scrubbed = $this->scrubRecursive($data, $user);

                if ($scrubbed !== $data) {
                    $log->event_data = $scrubbed;
                    $log->saveQuietly();
                }
            });
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @return array<int|string, mixed>
     */
    private function scrubRecursive(array $data, User $user): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->scrubRecursive($value, $user);

                continue;
            }

            if ($this->isScrubbableField($key)) {
                $data[$key] = self::SCRUB_TOKEN;

                continue;
            }

            if (is_string($value) && strcasecmp($value, $user->email) === 0) {
                $data[$key] = self::SCRUB_TOKEN;
            }
        }

        return $data;
    }

    private function isScrubbableField(int|string $key): bool
    {
        if (! is_string($key)) {
            return false;
        }

        return in_array(strtolower($key), self::SCRUB_FIELDS, true);
    }

    private function deleteIdentities(User $user): void
    {
        $user->studentData()->delete();
        $user->speakerData()->delete();
        $user->socialIdentities()->delete();
        $user->dataExportRequests()->delete();
    }

    private function scrubRatings(User $user): void
    {
        $user->ratings()
            ->whereNotNull('comment')
            ->update(['comment' => '[removido pelo usuário]']);
    }

    private function revokeTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    private function deleteSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
