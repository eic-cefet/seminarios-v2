<?php

namespace App\Console\Commands;

use App\Mail\CertificateGenerated;
use App\Mail\EvaluationReminder;
use App\Mail\SeminarReminder;
use App\Mail\WelcomeUser;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendTestEmailsCommand extends Command
{
    protected $signature = 'mail:send-tests
                            {--only= : Send only specific email type (welcome, certificate, reminder, evaluation)}
                            {--to= : Override the recipient email address}';

    protected $description = 'Send test emails with real temporary data to verify email templates';

    private array $createdIds = [
        'users' => [],
        'seminars' => [],
        'registrations' => [],
    ];

    public function handle(): int
    {
        $testEmail = $this->option('to') ?: config('mail.test_mail');

        if (empty($testEmail)) {
            $this->error('No test email configured. Set MAIL_TEST_MAIL in .env or use --to option.');

            return self::FAILURE;
        }

        $this->info("Sending test emails to: {$testEmail}");
        $this->newLine();

        $only = $this->option('only');

        $emailTypes = [
            'welcome' => 'Welcome User',
            'certificate' => 'Certificate Generated',
            'reminder' => 'Seminar Reminder',
            'evaluation' => 'Evaluation Reminder',
        ];

        if ($only && ! isset($emailTypes[$only])) {
            $this->error("Invalid email type: {$only}");
            $this->info('Available types: '.implode(', ', array_keys($emailTypes)));

            return self::FAILURE;
        }

        $typesToSend = $only ? [$only => $emailTypes[$only]] : $emailTypes;

        try {
            DB::beginTransaction();

            foreach ($typesToSend as $type => $name) {
                $this->sendTestEmail($type, $name, $testEmail);
            }

            $this->newLine();
            $this->info('All test emails sent successfully!');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            $this->cleanup();
            DB::rollBack();
        }
    }

    private function cleanup(): void
    {
        $this->newLine();
        $this->info('Cleaning up test data...');

        Registration::whereIn('id', $this->createdIds['registrations'])->delete();
        Seminar::whereIn('id', $this->createdIds['seminars'])->delete();
        User::whereIn('id', $this->createdIds['users'])->delete();

        $this->info('  ✓ Test data cleaned up');
    }

    private function sendTestEmail(string $type, string $name, string $testEmail): void
    {
        $this->info("Sending: {$name}...");

        $mailable = match ($type) {
            'welcome' => $this->createWelcomeMail(),
            'certificate' => $this->createCertificateMail(),
            'reminder' => $this->createReminderMail(),
            'evaluation' => $this->createEvaluationMail(),
        };

        $mailable->onConnection('sync');
        Mail::to($testEmail)->send($mailable);

        $this->info("  ✓ {$name} sent");
    }

    private function createTestUser(string $suffix = ''): User
    {
        $user = User::create([
            'name' => 'Usuário de Teste'.($suffix ? " {$suffix}" : ''),
            'email' => 'test-'.uniqid().'@example.com',
            'provider' => 'test',
            'provider_id' => 'test-'.uniqid(),
        ]);

        $this->createdIds['users'][] = $user->id;

        return $user;
    }

    private function createTestSeminar(string $name, ?Carbon $scheduledAt = null): Seminar
    {
        $locationId = SeminarLocation::first()?->id ?? 1;

        $seminar = Seminar::create([
            'name' => $name,
            'slug' => 'test-'.uniqid(),
            'description' => 'Este é um seminário de teste para verificar o template de email.',
            'scheduled_at' => $scheduledAt ?? Carbon::tomorrow()->setHour(14)->setMinute(0),
            'seminar_location_id' => $locationId,
        ]);

        $this->createdIds['seminars'][] = $seminar->id;

        return $seminar;
    }

    private function createTestRegistration(User $user, Seminar $seminar): Registration
    {
        $registration = Registration::create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
            'certificate_code' => 'TEST-'.strtoupper(substr(md5(now()->timestamp), 0, 8)),
            'certificate_sent' => false,
        ]);

        $this->createdIds['registrations'][] = $registration->id;

        return $registration;
    }

    private function createWelcomeMail(): WelcomeUser
    {
        $user = $this->createTestUser();

        return new WelcomeUser($user);
    }

    private function createCertificateMail(): CertificateGenerated
    {
        $user = $this->createTestUser();
        $seminar = $this->createTestSeminar('Seminário de Teste: Introdução à IA');
        $registration = $this->createTestRegistration($user, $seminar);

        $mockPdfContent = '%PDF-1.4 mock certificate content for testing';

        return new CertificateGenerated($registration, $mockPdfContent);
    }

    private function createReminderMail(): SeminarReminder
    {
        $user = $this->createTestUser();

        $seminars = collect([
            $this->createTestSeminar('Introdução ao Machine Learning'),
            $this->createTestSeminar('Desenvolvimento Web com Laravel'),
        ]);

        return new SeminarReminder($user, $seminars);
    }

    private function createEvaluationMail(): EvaluationReminder
    {
        $user = $this->createTestUser();

        $seminars = collect([
            $this->createTestSeminar('Workshop de Python', Carbon::yesterday()->setHour(14)),
            $this->createTestSeminar('Segurança em Aplicações Web', Carbon::yesterday()->setHour(16)),
        ]);

        return new EvaluationReminder($user, $seminars);
    }
}
