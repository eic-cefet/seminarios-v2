<?php

use App\Mail\ReportReady;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('builds the report ready email with correct subject and content', function () {
    $mailable = new ReportReady(
        reportName: 'Relatório Semestral 2025.1',
        downloadUrl: 'https://s3.example.com/report.xlsx?signed=1',
    );

    $mailable->assertHasSubject('Relatório pronto: Relatório Semestral 2025.1');
    $mailable->assertSeeInHtml('Relatório Semestral 2025.1');
    $mailable->assertSeeInHtml('2 horas');
    $mailable->assertSeeInHtml('https://s3.example.com/report.xlsx?signed=1');
});

it('can be sent to a recipient', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);

    Mail::to($user->email)->send(new ReportReady(
        reportName: 'Logs de Auditoria — últimos 30 dias',
        downloadUrl: 'https://s3.example.com/audit.xlsx',
    ));

    Mail::assertSent(ReportReady::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email)
            && $mail->reportName === 'Logs de Auditoria — últimos 30 dias';
    });
});
