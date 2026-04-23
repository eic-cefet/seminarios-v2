<?php

use App\Models\Seminar;
use App\Models\SeminarAlertDispatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('belongs to a user and a seminar', function () {
    Bus::fake();
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create();

    $dispatch = SeminarAlertDispatch::create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'sent_at' => now(),
    ]);

    expect($dispatch->user->is($user))->toBeTrue();
    expect($dispatch->seminar->is($seminar))->toBeTrue();
});
