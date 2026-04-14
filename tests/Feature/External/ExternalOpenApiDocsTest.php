<?php

describe('GET /api/external/docs.json', function () {
    it('returns the OpenAPI specification', function () {
        $response = $this->getJson('/api/external/docs.json');

        $response->assertSuccessful();

        $spec = $response->json();
        expect($spec['openapi'])->toStartWith('3.');
        expect($spec['info']['title'])->toBe('Seminários EIC - External API');
    });

    it('includes bearer security scheme', function () {
        $response = $this->getJson('/api/external/docs.json');

        $securitySchemes = $response->json('components.securitySchemes');
        expect($securitySchemes)->toHaveKey('http');
        expect($securitySchemes['http']['type'])->toBe('http');
        expect($securitySchemes['http']['scheme'])->toBe('bearer');
    });

    it('uses clean tag names without External prefix', function () {
        $response = $this->getJson('/api/external/docs.json');

        $tags = collect($response->json('paths'))
            ->flatMap(fn ($methods) => collect($methods)
                ->filter(fn ($v) => is_array($v) && isset($v['tags']))
                ->pluck('tags')
                ->flatten())
            ->unique()
            ->values()
            ->all();

        expect($tags)->toContain('Seminars');
        expect($tags)->toContain('Seminar Types');
        expect($tags)->toContain('Seminar Locations');
        expect($tags)->toContain('Users');
        expect($tags)->toContain('Speaker Data');
        expect($tags)->toContain('Workshops');
        expect($tags)->not->toContain('ExternalSeminar');
        expect($tags)->not->toContain('ExternalLocation');
        expect($tags)->not->toContain('ExternalSeminarType');
        expect($tags)->not->toContain('ExternalUser');
        expect($tags)->not->toContain('ExternalSpeakerData');
        expect($tags)->not->toContain('ExternalWorkshop');
    });

    it('uses default schema names from Scramble', function () {
        $response = $this->getJson('/api/external/docs.json');

        $schemas = array_keys($response->json('components.schemas'));

        expect($schemas)->toContain('ExternalSeminarResource');
        expect($schemas)->toContain('ExternalSpeakerResource');
        expect($schemas)->toContain('ExternalUserResource');
        expect($schemas)->toContain('ExternalSpeakerDataResource');
        expect($schemas)->toContain('ExternalWorkshopResource');
        expect($schemas)->toContain('ExternalSeminarStoreRequest');
        expect($schemas)->toContain('ExternalSeminarUpdateRequest');
    });

    it('strips external prefix from operation IDs', function () {
        $response = $this->getJson('/api/external/docs.json');

        $operationIds = collect($response->json('paths'))
            ->flatMap(fn ($methods) => collect($methods)
                ->filter(fn ($v) => is_array($v) && isset($v['operationId']))
                ->pluck('operationId'))
            ->all();

        foreach ($operationIds as $id) {
            expect($id)->not->toStartWith('external.');
        }

        expect($operationIds)->toContain('seminars.index');
        expect($operationIds)->toContain('users.speaker-data.show');
    });
});
