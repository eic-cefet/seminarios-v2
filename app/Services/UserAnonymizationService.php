<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            Storage::disk('s3')->delete($path);
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
            ->each(function (AuditLog $log): void {
                $data = $log->event_data ?? [];
                foreach (['old_values', 'new_values'] as $bucket) {
                    if (! isset($data[$bucket]) || ! is_array($data[$bucket])) {
                        continue;
                    }
                    foreach (self::SCRUB_FIELDS as $field) {
                        if (array_key_exists($field, $data[$bucket])) {
                            $data[$bucket][$field] = self::SCRUB_TOKEN;
                        }
                    }
                }
                $log->event_data = $data;
                $log->saveQuietly();
            });
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
