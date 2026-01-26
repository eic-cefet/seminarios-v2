<?php

use App\Http\Resources\SpeakerResource;
use App\Models\User;
use App\Models\UserSpeakerData;
use Illuminate\Http\Request;

describe('SpeakerResource', function () {
    it('returns user id and name', function () {
        $user = User::factory()->create([
            'name' => 'John Speaker',
        ]);

        $resource = new SpeakerResource($user);
        $array = $resource->toArray(new Request);

        expect($array['id'])->toBe($user->id);
        expect($array['name'])->toBe('John Speaker');
    });

    it('includes speakerData when loaded', function () {
        $user = User::factory()->create();
        UserSpeakerData::factory()->create([
            'user_id' => $user->id,
            'institution' => 'CEFET-RJ',
            'description' => 'Expert in AI',
        ]);

        // Load the relationship
        $user->load('speakerData');

        $resource = new SpeakerResource($user);
        $array = $resource->toArray(new Request);

        expect($array)->toHaveKey('speakerData');
    });

    it('returns collection of speakers', function () {
        $users = User::factory()->count(2)->create();

        $collection = SpeakerResource::collection($users);

        expect($collection->count())->toBe(2);
    });

    it('can wrap response in data key', function () {
        $user = User::factory()->create([
            'name' => 'Test Speaker',
        ]);

        $resource = new SpeakerResource($user);
        $response = $resource->response()->getData(true);

        expect($response)->toHaveKey('data');
        expect($response['data']['name'])->toBe('Test Speaker');
    });
});
