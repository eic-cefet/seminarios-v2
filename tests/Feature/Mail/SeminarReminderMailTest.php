<?php

use App\Mail\SeminarReminder;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\SeminarType;
use App\Models\User;

describe('SeminarReminder Mail', function () {
    it('has singular subject for one seminar', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create(['seminar_type_id' => null]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Lembrete: Seminário amanhã! - '.config('mail.name'));
    });

    it('has plural subject for multiple seminars', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(3)->create(['seminar_type_id' => null]);

        $mail = new SeminarReminder($user, $seminars);
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Lembrete: 3 apresentações amanhã! - '.config('mail.name'));
    });

    it('uses markdown template', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();

        $mail = new SeminarReminder($user, collect([$seminar]));
        $content = $mail->content();

        expect($content->markdown)->toBe('emails.seminar-reminder');
    });

    it('passes user name to template', function () {
        $user = User::factory()->create([
            'name' => 'Roberto Lima',
        ]);
        $seminar = Seminar::factory()->create();

        $mail = new SeminarReminder($user, collect([$seminar]));
        $content = $mail->content();

        expect($content->with['userName'])->toBe('Roberto Lima');
    });

    it('passes seminars collection to template', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create();

        $mail = new SeminarReminder($user, $seminars);
        $content = $mail->content();

        expect($content->with['seminars'])->toHaveCount(2);
    });

    it('generates ics attachment for seminar with scheduled date', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'name' => 'AI Workshop',
            'slug' => 'ai-workshop',
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('generates multiple ics attachments for multiple seminars', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create([
            'scheduled_at' => now()->addDay(),
        ]);

        $mail = new SeminarReminder($user, $seminars);
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(2);
    });

    it('generates ics for all seminars with scheduled dates', function () {
        $user = User::factory()->create();
        $seminar1 = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);
        $seminar2 = Seminar::factory()->create([
            'scheduled_at' => now()->addDays(2),
        ]);

        $mail = new SeminarReminder($user, collect([$seminar1, $seminar2]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(2);
    });

    it('includes location in ics when available', function () {
        $user = User::factory()->create();
        $location = SeminarLocation::factory()->create([
            'name' => 'Room 101',
        ]);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'seminar_location_id' => $location->id,
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('includes room link in ics description when available', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'room_link' => 'https://meet.example.com/room123',
            'description' => 'Test seminar',
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('uses seminar slug for ics filename', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'slug' => 'my-seminar-slug',
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('handles seminar without description', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'description' => null,
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('handles room link without description', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'description' => null,
            'room_link' => 'https://meet.example.com/room',
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('stores user reference', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();

        $mail = new SeminarReminder($user, collect([$seminar]));

        expect($mail->user->id)->toBe($user->id);
    });

    it('subject uses {Type} for a single seminar', function () {
        $type = SeminarType::factory()->feminine()->create([
            'name' => 'Dissertação',
            'name_plural' => 'Dissertações',
        ]);
        $seminar = Seminar::factory()->for($type, 'seminarType')->create(['name' => 'X']);
        $user = User::factory()->create();

        $mail = new SeminarReminder($user, collect([$seminar]));

        expect($mail->envelope()->subject)->toBe('Lembrete: Dissertação amanhã! - '.config('mail.name'));
    });

    it('subject uses single-type plural when all seminars share one type', function () {
        $type = SeminarType::factory()->feminine()->create([
            'name' => 'Dissertação',
            'name_plural' => 'Dissertações',
        ]);
        $seminars = Seminar::factory()->count(3)->for($type, 'seminarType')->create();
        $user = User::factory()->create();

        $mail = new SeminarReminder($user, $seminars);

        expect($mail->envelope()->subject)->toBe('Lembrete: 3 dissertações amanhã! - '.config('mail.name'));
    });

    it('subject falls back to "apresentações" for mixed types', function () {
        $masc = SeminarType::factory()->masculine()->create([
            'name' => 'Seminário',
            'name_plural' => 'Seminários',
        ]);
        $fem = SeminarType::factory()->feminine()->create([
            'name' => 'Dissertação',
            'name_plural' => 'Dissertações',
        ]);
        $seminars = collect([
            Seminar::factory()->for($masc, 'seminarType')->create(),
            Seminar::factory()->for($fem, 'seminarType')->create(),
        ])->each->load('seminarType');
        $user = User::factory()->create();

        $mail = new SeminarReminder($user, $seminars);

        expect($mail->envelope()->subject)->toBe('Lembrete: 2 apresentações amanhã! - '.config('mail.name'));
    });

    it('singular body uses the seminar type article and noun', function () {
        $type = SeminarType::factory()->feminine()->create([
            'name' => 'Dissertação',
            'name_plural' => 'Dissertações',
        ]);
        $seminar = Seminar::factory()->for($type, 'seminarType')->create();
        $user = User::factory()->create();

        $rendered = (new SeminarReminder($user, collect([$seminar])))->render();

        expect($rendered)
            ->toContain('Lembrete de Dissertação')
            ->toContain('Você está inscrito na dissertação que acontecerá')
            ->toContain('Ver Detalhes da Dissertação');
    });

    it('plural body for mixed types uses "apresentações" with feminine articles', function () {
        $masc = SeminarType::factory()->masculine()->create([
            'name' => 'Seminário',
            'name_plural' => 'Seminários',
        ]);
        $fem = SeminarType::factory()->feminine()->create([
            'name' => 'Dissertação',
            'name_plural' => 'Dissertações',
        ]);
        $seminars = collect([
            Seminar::factory()->for($masc, 'seminarType')->create(),
            Seminar::factory()->for($fem, 'seminarType')->create(),
        ])->each->load('seminarType');
        $user = User::factory()->create();

        $rendered = (new SeminarReminder($user, $seminars))->render();

        expect($rendered)
            ->toContain('Lembrete de Apresentações')
            ->toContain('Você está inscrito nas apresentações que acontecerão');
    });

    it('plural body for single shared type uses that type plural', function () {
        $type = SeminarType::factory()->feminine()->create([
            'name' => 'Dissertação',
            'name_plural' => 'Dissertações',
        ]);
        $seminars = Seminar::factory()->count(2)->for($type, 'seminarType')->create();
        $user = User::factory()->create();

        $rendered = (new SeminarReminder($user, $seminars))->render();

        expect($rendered)->toContain('Você está inscrito nas dissertações que acontecerão');
    });

});
