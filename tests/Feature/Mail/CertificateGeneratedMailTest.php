<?php

use App\Mail\CertificateGenerated;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

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

    it('stores the s3 pdf path, not the binary content', function () {
        $registration = Registration::factory()->create([
            'certificate_code' => 'PATH-TEST',
        ]);

        $pdfPath = 'certificates/2024/test/path-test.pdf';
        $mail = new CertificateGenerated($registration, $pdfPath);

        expect($mail->pdfPath)->toBe($pdfPath);
    });

    it('throws a descriptive runtime exception when the s3 pdf is missing at send time', function () {
        Storage::fake('s3');
        // Intentionally do NOT put any file on S3.

        $registration = Registration::factory()->create([
            'certificate_code' => 'MISSING-S3',
        ]);

        $mail = new CertificateGenerated($registration, 'certs/missing.pdf');
        $attachment = $mail->attachments()[0];

        expect(fn () => $attachment->attachWith(
            fn () => null,
            function (Closure $data) {
                $data();
            }
        ))->toThrow(RuntimeException::class, 'certs/missing.pdf');
    });

    it('lazily loads attachment bytes from s3 at send time', function () {
        Storage::fake('s3');

        $binaryPdf = "%PDF-1.4\n\x00\xFF\xFE\nlazy bytes\n%%EOF";
        Storage::disk('s3')->put('certs/lazy.pdf', $binaryPdf);

        $registration = Registration::factory()->create([
            'certificate_code' => 'LAZY-TEST',
        ]);

        $mail = new CertificateGenerated($registration, 'certs/lazy.pdf');

        // Serializing the mailable for queueing must not embed the binary.
        expect(serialize($mail))->not->toContain($binaryPdf);

        // But at attachment build time we should still have the bytes.
        $attachment = $mail->attachments()[0];
        $resolved = null;
        $attachment->attachWith(
            fn () => null,
            function (Closure $data) use (&$resolved) {
                $resolved = $data();
            }
        );
        expect($resolved)->toBe($binaryPdf);
    });

    it('uses feminine article in body for a feminine type', function () {
        Storage::fake('s3');
        Storage::disk('s3')->put('certs/fem.pdf', '%PDF-test');

        $type = SeminarType::factory()->feminine()->create([
            'name' => 'Dissertação',
            'name_plural' => 'Dissertações',
        ]);
        $seminar = Seminar::factory()->for($type, 'seminarType')->create([
            'name' => 'Método X',
            'scheduled_at' => now()->subDay(),
        ]);
        $registration = Registration::factory()->for($seminar)->create([
            'certificate_code' => 'TEST-FEM',
        ]);

        $rendered = (new CertificateGenerated($registration, 'certs/fem.pdf'))->render();

        expect($rendered)
            ->toContain('Parabéns por sua participação na dissertação <strong')
            ->toContain('Método X')
            ->toContain('realizada em');
    });

    it('uses masculine article in body for a masculine type', function () {
        Storage::fake('s3');
        Storage::disk('s3')->put('certs/masc.pdf', '%PDF-test');

        $type = SeminarType::factory()->masculine()->create([
            'name' => 'Seminário',
            'name_plural' => 'Seminários',
        ]);
        $seminar = Seminar::factory()->for($type, 'seminarType')->create([
            'name' => 'Tópico Y',
            'scheduled_at' => now()->subDay(),
        ]);
        $registration = Registration::factory()->for($seminar)->create([
            'certificate_code' => 'TEST-MAS',
        ]);

        $rendered = (new CertificateGenerated($registration, 'certs/masc.pdf'))->render();

        expect($rendered)
            ->toContain('Parabéns por sua participação no seminário <strong')
            ->toContain('Tópico Y')
            ->toContain('realizado em');
    });

    it('falls back to "no seminário ... realizado" when type is null', function () {
        Storage::fake('s3');
        Storage::disk('s3')->put('certs/null.pdf', '%PDF-test');

        $seminar = Seminar::factory()->create([
            'name' => 'Sem tipo',
            'seminar_type_id' => null,
            'scheduled_at' => now()->subDay(),
        ]);
        $registration = Registration::factory()->for($seminar)->create([
            'certificate_code' => 'TEST-NULL',
        ]);

        $rendered = (new CertificateGenerated($registration, 'certs/null.pdf'))->render();

        expect($rendered)
            ->toContain('Parabéns por sua participação no seminário <strong')
            ->toContain('Sem tipo')
            ->toContain('realizado em');
    });
});
