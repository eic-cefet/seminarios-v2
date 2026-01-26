<?php

use App\Mail\CertificateGenerated;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;

describe('CertificateGenerated Mail', function () {
    it('has correct subject with seminar name', function () {
        $seminar = Seminar::factory()->create([
            'name' => 'Introduction to AI',
        ]);
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'certificate_code' => 'CERT123',
        ]);

        $mail = new CertificateGenerated($registration, 'pdf-content');
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Seu Certificado de Participação - Introduction to AI');
    });

    it('uses markdown template', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => 'CERT456',
        ]);

        $mail = new CertificateGenerated($registration, 'pdf-content');
        $content = $mail->content();

        expect($content->markdown)->toBe('emails.certificate-generated');
    });

    it('passes user name to template', function () {
        $user = User::factory()->create([
            'name' => 'Carlos Santos',
        ]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'certificate_code' => 'CERT789',
        ]);

        $mail = new CertificateGenerated($registration, 'pdf-content');
        $content = $mail->content();

        expect($content->with['userName'])->toBe('Carlos Santos');
    });

    it('passes seminar name to template', function () {
        $seminar = Seminar::factory()->create([
            'name' => 'Web Development Workshop',
        ]);
        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'certificate_code' => 'CERT001',
        ]);

        $mail = new CertificateGenerated($registration, 'pdf-content');
        $content = $mail->content();

        expect($content->with['seminarName'])->toBe('Web Development Workshop');
    });

    it('passes formatted seminar date to template', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => '2024-06-15 14:00:00',
        ]);
        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'certificate_code' => 'CERT002',
        ]);

        $mail = new CertificateGenerated($registration, 'pdf-content');
        $content = $mail->content();

        expect($content->with['seminarDate'])->toBe('15/06/2024');
    });

    it('passes certificate code to template', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => 'UNIQUE-CODE-123',
        ]);

        $mail = new CertificateGenerated($registration, 'pdf-content');
        $content = $mail->content();

        expect($content->with['certificateCode'])->toBe('UNIQUE-CODE-123');
    });

    it('passes certificate url to template', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => 'URL-CODE',
        ]);

        $mail = new CertificateGenerated($registration, 'pdf-content');
        $content = $mail->content();

        expect($content->with['certificateUrl'])->toBe(url('/certificado/URL-CODE'));
    });

    it('attaches pdf with correct filename', function () {
        $seminar = Seminar::factory()->create([
            'slug' => 'my-seminar',
        ]);
        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'certificate_code' => 'PDF-TEST',
        ]);

        $mail = new CertificateGenerated($registration, 'mock-pdf-content');
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('stores pdf content', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => 'CONTENT-TEST',
        ]);

        $pdfContent = '%PDF-1.4 mock content';
        $mail = new CertificateGenerated($registration, $pdfContent);

        expect($mail->pdfContent)->toBe($pdfContent);
    });
});
