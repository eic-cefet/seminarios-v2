<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Str;
use ZipArchive;

class UserDataExportService
{
    /**
     * Build the structured export payload for a user.
     *
     * @return array<string, mixed>
     */
    public function collect(User $user): array
    {
        $user->loadMissing([
            'studentData.course',
            'speakerData',
            'registrations.seminar:id,slug,title,scheduled_at',
            'ratings.seminar:id,slug,title',
            'consents',
            'socialIdentities:id,user_id,provider,provider_id,token_expires_at,created_at',
        ]);

        return [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
                'roles' => $user->roles->pluck('name'),
                'student_data' => $user->studentData ? [
                    'course' => $user->studentData->course?->name,
                    'course_situation' => $user->studentData->course_situation?->value,
                    'course_role' => $user->studentData->course_role?->value,
                ] : null,
                'speaker_data' => $user->speakerData ? [
                    'slug' => $user->speakerData->slug,
                    'institution' => $user->speakerData->institution,
                    'description' => $user->speakerData->description,
                ] : null,
            ],
            'registrations' => $user->registrations->map(fn ($r) => [
                'seminar' => [
                    'slug' => $r->seminar?->slug,
                    'title' => $r->seminar?->title,
                    'scheduled_at' => $r->seminar?->scheduled_at?->toIso8601String(),
                ],
                'present' => (bool) $r->present,
                'certificate_code' => $r->certificate_code,
                'registered_at' => $r->created_at?->toIso8601String(),
            ])->values()->all(),
            'ratings' => $user->ratings->map(fn ($r) => [
                'seminar' => [
                    'slug' => $r->seminar?->slug,
                    'title' => $r->seminar?->title,
                ],
                'score' => $r->score,
                'comment' => $r->comment,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->values()->all(),
            'consents' => $user->consents->map(fn ($c) => [
                'type' => $c->type->value,
                'granted' => $c->granted,
                'version' => $c->version,
                'source' => $c->source,
                'ip_address' => $c->ip_address,
                'recorded_at' => $c->created_at?->toIso8601String(),
            ])->values()->all(),
            'social_identities' => $user->socialIdentities->map(fn ($s) => [
                'provider' => $s->provider,
                'provider_id' => $s->provider_id,
                'token_expires_at' => $s->token_expires_at?->toIso8601String(),
                'linked_at' => $s->created_at?->toIso8601String(),
            ])->values()->all(),
            'audit_events' => AuditLog::query()
                ->where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(
                    config('lgpd.retention.audit_logs_days', 90),
                ))
                ->orderByDesc('created_at')
                ->get(['event_name', 'event_type', 'ip_address', 'created_at'])
                ->map(fn ($log) => [
                    'event' => $log->event_name,
                    'type' => $log->event_type,
                    'ip' => $log->ip_address,
                    'at' => $log->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Write a ZIP file to the local temp directory and return its path.
     */
    public function writeZip(User $user): string
    {
        $payload = $this->collect($user);

        $tmp = storage_path('app/tmp/lgpd-export-'.$user->id.'-'.Str::random(8).'.zip');
        if (! is_dir(dirname($tmp))) {
            mkdir(dirname($tmp), 0775, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { // @codeCoverageIgnore
            throw new \RuntimeException('Unable to create export ZIP.'); // @codeCoverageIgnore
        }

        $encode = fn (mixed $d) => json_encode(
            $d,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        $zip->addFromString('profile.json', $encode($payload['profile']));
        $zip->addFromString('registrations.json', $encode($payload['registrations']));
        $zip->addFromString('ratings.json', $encode($payload['ratings']));
        $zip->addFromString('consents.json', $encode($payload['consents']));
        $zip->addFromString('social_identities.json', $encode($payload['social_identities']));
        $zip->addFromString('audit_events.json', $encode($payload['audit_events']));
        $zip->addFromString('README.txt', $this->readme($user, $payload['exported_at']));
        $zip->close();

        return $tmp;
    }

    private function readme(User $user, string $exportedAt): string
    {
        $dpo = config('lgpd.encarregado.email', 'lgpd@eic-seminarios.com');

        return <<<TXT
Exportação de dados pessoais — Sistema de Seminários da EIC / CEFET-RJ

Titular: {$user->name} ({$user->email})
Data da exportação: {$exportedAt}

Esta pasta compactada contém todos os dados pessoais que mantemos sobre você,
organizados nos seguintes arquivos JSON:

- profile.json — identificação, papel, dados de aluno/palestrante.
- registrations.json — inscrições e presenças nos seminários.
- ratings.json — avaliações e comentários enviados.
- consents.json — histórico completo de consentimentos.
- social_identities.json — vínculos com provedores OAuth (sem tokens).
- audit_events.json — eventos de auditoria dos últimos 90 dias.

Este arquivo atende ao direito de portabilidade previsto no Art. 18, V da LGPD.
Em caso de dúvida ou para solicitações adicionais, entre em contato com o
Encarregado pelo Tratamento de Dados Pessoais: {$dpo}.
TXT;
    }
}
