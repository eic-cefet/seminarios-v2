<?php

use App\Mail\SeminarReminder7d;
use App\Models\Seminar;
use App\Models\User;

it('renders the 7-day reminder copy and subject', function () {
    $user = User::factory()->create(['name' => 'Bia']);
    $seminars = collect([Seminar::factory()->create(['name' => 'Compiladores', 'scheduled_at' => now()->addDays(7)])]);

    $mailable = new SeminarReminder7d($user, $seminars);

    $mailable->assertHasSubject('Lembrete: Seminário na próxima semana! - '.config('mail.name'));
    $mailable->assertSeeInHtml('Bia');
    $mailable->assertSeeInHtml('Compiladores');
    $mailable->assertSeeInHtml('na próxima semana');
});
