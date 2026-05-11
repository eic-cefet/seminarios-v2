<?php

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

describe('NotificationResource', function () {
    it('maps all fields when data is fully populated', function () {
        $id = (string) Str::uuid();
        $notification = new DatabaseNotification([
            'id' => $id,
            'type' => 'App\\Notifications\\CertificateReadyNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => 1,
            'data' => [
                'category' => 'certificate_ready',
                'title' => 'Seu certificado está pronto',
                'body' => 'O certificado está disponível.',
                'action_url' => '/certificados',
            ],
            'read_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $array = (new NotificationResource($notification))->toArray(new Request);

        expect($array['id'])->toBe($id);
        expect($array['category'])->toBe('certificate_ready');
        expect($array['title'])->toBe('Seu certificado está pronto');
        expect($array['body'])->toBe('O certificado está disponível.');
        expect($array['action_url'])->toBe('/certificados');
        expect($array['read_at'])->not->toBeNull();
        expect($array['created_at'])->not->toBeNull();
    });

    it('returns null for missing data keys', function () {
        $notification = new DatabaseNotification([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\CertificateReadyNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => 1,
            'data' => [],
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $array = (new NotificationResource($notification))->toArray(new Request);

        expect($array['category'])->toBeNull();
        expect($array['title'])->toBeNull();
        expect($array['body'])->toBeNull();
        expect($array['action_url'])->toBeNull();
        expect($array['read_at'])->toBeNull();
    });

    it('returns null action_url when absent from data', function () {
        $notification = new DatabaseNotification([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\EvaluationDueNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => 1,
            'data' => [
                'category' => 'evaluation_due',
                'title' => 'Avaliação pendente',
                'body' => 'Avalie a apresentação "Test Seminar".',
            ],
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $array = (new NotificationResource($notification))->toArray(new Request);

        expect($array['action_url'])->toBeNull();
    });
});
