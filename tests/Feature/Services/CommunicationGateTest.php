<?php

use App\Enums\CommunicationCategory;
use App\Models\User;
use App\Services\CommunicationGate;

it('mirrors the user preference', function () {
    $user = User::factory()->create();
    $user->alertPreference()->create([
        CommunicationCategory::CertificateReady->column() => false,
    ]);

    expect((new CommunicationGate)->canEmail($user->fresh(), CommunicationCategory::CertificateReady))
        ->toBeFalse();
});

it('defaults to allow when no preference is recorded', function () {
    $user = User::factory()->create();

    expect((new CommunicationGate)->canEmail($user, CommunicationCategory::CertificateReady))
        ->toBeTrue();
});
