<?php

use App\Mail\SeminarRescheduled;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

describe('SeminarRescheduled Mail', function () {
    it('has correct subject line', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create(['name' => 'AI Workshop']);
        $oldDate = now()->subDay();

        $mail = new SeminarRescheduled($user, $seminar, $oldDate);
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Apresentação reagendada: AI Workshop - '.config('mail.name'));
    });

    it('uses gender-neutral feminine "apresentação" in subject and body', function () {
        $user = User::factory()->create(['name' => 'Joana']);
        $seminar = Seminar::factory()->create([
            'name' => 'AI Workshop',
            'scheduled_at' => now()->addDays(7),
        ]);
        $oldDate = now()->addDay();

        $mail = new SeminarRescheduled($user, $seminar, $oldDate);

        expect($mail->envelope()->subject)->toStartWith('Apresentação reagendada: AI Workshop');

        $rendered = $mail->render();
        expect($rendered)
            ->toContain('Apresentação Reagendada')
            ->toContain('A apresentação')
            ->toContain('foi reagendada:')
            ->toContain('Ver Detalhes da Apresentação')
            ->not->toContain('Seminário Reagendado')
            ->not->toContain('O seminário')
            ->not->toContain('foi reagendado:')
            ->not->toContain('Detalhes do Seminário');
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
});
