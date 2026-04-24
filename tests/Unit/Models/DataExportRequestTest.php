<?php

use App\Models\DataExportRequest;
use App\Models\User;

it('creates a data export request linked to a user', function () {
    $user = User::factory()->create();
    $request = DataExportRequest::factory()->for($user)->create();

    expect($request->user_id)->toBe($user->id)
        ->and($request->status)->toBe('queued');
});

it('marks as completed with file path and expiration', function () {
    $request = DataExportRequest::factory()->create();
    $request->markCompleted('lgpd-exports/2026/export-abc.zip', now()->addHours(48));

    expect($request->fresh()->status)->toBe('completed')
        ->and($request->fresh()->file_path)->toBe('lgpd-exports/2026/export-abc.zip');
});
