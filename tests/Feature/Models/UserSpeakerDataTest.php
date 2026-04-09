<?php

use App\Models\User;
use App\Models\UserSpeakerData;

describe('UserSpeakerData Model', function () {
    it('belongs to a user', function () {
        $user = User::factory()->create();
        $speakerData = UserSpeakerData::factory()->create([
            'user_id' => $user->id,
        ]);

        expect($speakerData->user->id)->toBe($user->id);
    });

    it('can create with factory', function () {
        $speakerData = UserSpeakerData::factory()->create();

        expect($speakerData->exists)->toBeTrue();
        expect($speakerData->slug)->not->toBeEmpty();
    });

    it('can create with institution state', function () {
        $speakerData = UserSpeakerData::factory()->withInstitution()->create();

        expect($speakerData->institution)->not->toBeNull();
    });

    it('can create with description state', function () {
        $speakerData = UserSpeakerData::factory()->withDescription()->create();

        expect($speakerData->description)->not->toBeNull();
    });

    it('has fillable attributes', function () {
        $user = User::factory()->create();

        $speakerData = UserSpeakerData::create([
            'user_id' => $user->id,
            'slug' => 'john-doe',
            'institution' => 'CEFET-RJ',
            'description' => 'A renowned speaker',
        ]);

        expect($speakerData->slug)->toBe('john-doe');
        expect($speakerData->institution)->toBe('CEFET-RJ');
        expect($speakerData->description)->toBe('A renowned speaker');
    });

    it('uses correct table name', function () {
        $speakerData = new UserSpeakerData;

        expect($speakerData->getTable())->toBe('user_speaker_data');
    });
});
