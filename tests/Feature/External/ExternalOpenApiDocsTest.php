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
        expect($tags)->not->toContain('ExternalSeminar');
        expect($tags)->not->toContain('ExternalLocation');
        expect($tags)->not->toContain('ExternalSeminarType');
    });

    it('uses clean schema names without External prefix', function () {
        $response = $this->getJson('/api/external/docs.json');

        $schemas = array_keys($response->json('components.schemas'));

        expect($schemas)->toContain('Seminar');
        expect($schemas)->toContain('Speaker');
        expect($schemas)->toContain('SeminarStoreRequest');
        expect($schemas)->toContain('SeminarUpdateRequest');
        expect($schemas)->not->toContain('ExternalSeminarResource');
        expect($schemas)->not->toContain('ExternalSpeakerResource');
        expect($schemas)->not->toContain('ExternalSeminarStoreRequest');
        expect($schemas)->not->toContain('ExternalSeminarUpdateRequest');
    });
});
