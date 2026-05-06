<?php

use App\Mail\SeminarRescheduled;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

describe('SeminarRescheduled Mail', function () {
    it('has correct subject line', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create(['name' => 'AI Workshop', 'seminar_type_id' => null]);
        $oldDate = now()->subDay();

        $mail = new SeminarRescheduled($user, $seminar, $oldDate);
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Seminário reagendado: AI Workshop - '.config('mail.name'));
    });

    it('uses markdown template', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();
        $oldDate = now()->subDay();

        $mail = new SeminarRescheduled($user, $seminar, $oldDate);
        $content = $mail->content();

        expect($content->markdown)->toBe('emails.seminar-rescheduled');
    });

    it('passes correct data to template', function () {
        $user = User::factory()->create(['name' => 'Maria Santos']);
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
        $oldDate = now()->addDay();

        $mail = new SeminarRescheduled($user, $seminar, $oldDate);
        $content = $mail->content();

        expect($content->with['userName'])->toBe('Maria Santos');
        expect($content->with['seminar']->id)->toBe($seminar->id);
        expect($content->with['oldScheduledAt'])->toBe($oldDate);
        expect($content->with['newScheduledAt']->equalTo($seminar->scheduled_at))->toBeTrue();
    });

    it('has ics attachment with correct mime type', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
        $oldDate = now()->addDay();

        $mail = new SeminarRescheduled($user, $seminar, $oldDate);
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('stores user seminar and old scheduled at references', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();
        $oldDate = now()->subDay();

        $mail = new SeminarRescheduled($user, $seminar, $oldDate);

        expect($mail->user->id)->toBe($user->id);
        expect($mail->seminar->id)->toBe($seminar->id);
        expect($mail->oldScheduledAt)->toBe($oldDate);
    });

    it('returns empty attachments when seminar has no scheduled at', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();
        $seminar->scheduled_at = null;
        $oldDate = now()->subDay();

        $mail = new SeminarRescheduled($user, $seminar, $oldDate);
        $attachments = $mail->attachments();

        expect($attachments)->toBeEmpty();
    });

    it('does not implement should queue', function () {
        expect(SeminarRescheduled::class)->not->toImplement(ShouldQueue::class);
    });

    it('uses "{Type} reagendad{o|a}" subject and body for a feminine type', function () {
        $type = SeminarType::factory()->feminine()->create([
            'name' => 'Dissertação',
            'name_plural' => 'Dissertações',
        ]);
        $seminar = Seminar::factory()->for($type, 'seminarType')->create([
            'name' => 'Método X',
            'scheduled_at' => now()->addDay(),
        ]);
        $user = User::factory()->create();
        $oldScheduledAt = now()->subDay();

        $mail = new SeminarRescheduled($user, $seminar, $oldScheduledAt);

        expect($mail->envelope()->subject)->toBe('Dissertação reagendada: Método X - '.config('mail.name'));

        $rendered = $mail->render();
        expect($rendered)
            ->toContain('Dissertação Reagendada')
            ->toContain('A dissertação <strong')
            ->toContain('Método X')
            ->toContain('foi reagendada')
            ->toContain('Ver Detalhes da Dissertação');
    });

    it('keeps masculine wording for a masculine type', function () {
        $type = SeminarType::factory()->masculine()->create([
            'name' => 'Seminário',
            'name_plural' => 'Seminários',
        ]);
        $seminar = Seminar::factory()->for($type, 'seminarType')->create([
            'name' => 'Tópico Y',
            'scheduled_at' => now()->addDay(),
        ]);
        $user = User::factory()->create();
        $oldScheduledAt = now()->subDay();

        $mail = new SeminarRescheduled($user, $seminar, $oldScheduledAt);

        expect($mail->envelope()->subject)->toBe('Seminário reagendado: Tópico Y - '.config('mail.name'));

        $rendered = $mail->render();
        expect($rendered)
            ->toContain('Seminário Reagendado')
            ->toContain('O seminário <strong')
            ->toContain('Tópico Y')
            ->toContain('foi reagendado')
            ->toContain('Ver Detalhes do Seminário');
    });

    it('falls back to "Seminário reagendado" when seminar has no type', function () {
        $seminar = Seminar::factory()->create([
            'name' => 'Sem tipo',
            'seminar_type_id' => null,
            'scheduled_at' => now()->addDay(),
        ]);
        $user = User::factory()->create();
        $oldScheduledAt = now()->subDay();

        $mail = new SeminarRescheduled($user, $seminar, $oldScheduledAt);

        expect($mail->envelope()->subject)->toBe('Seminário reagendado: Sem tipo - '.config('mail.name'));
    });
});
