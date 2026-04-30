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

    it('declares an ApiError component schema', function () {
        $response = $this->getJson('/api/external/docs.json');
        $response->assertSuccessful();

        $schema = data_get($response->json(), 'components.schemas.ApiError');

        expect($schema)->not->toBeNull();
        expect($schema['type'])->toBe('object');
        expect($schema['properties'])->toHaveKeys(['error', 'message', 'errors']);
        expect($schema['required'])->toEqual(['error', 'message']);
    });

    it('declares ApiError as the default error response on every external operation', function () {
        $doc = $this->getJson('/api/external/docs.json')->json();

        // Pin a known operation so a future silent-skip regression (e.g. path
        // prefix mismatch between the provider and the rendered doc) fails
        // loudly instead of being satisfied by a vacuous "checked > 0".
        expect(data_get($doc, 'paths./v1/seminars.get.responses.default.content.application/json.schema.$ref'))
            ->toBe('#/components/schemas/ApiError');

        $externalPaths = collect(data_get($doc, 'paths', []))
            ->filter(fn ($methods, string $path) => str_starts_with($path, '/v1/'));

        expect($externalPaths)->not->toBeEmpty('No /v1/ paths found in the external OpenAPI doc.');

        $checked = 0;
        foreach ($externalPaths as $path => $methods) {
            foreach ($methods as $method => $op) {
                if (! is_array($op) || ! isset($op['responses'])) {
                    continue;
                }

                $schemaRef = data_get($op, 'responses.default.content.application/json.schema.$ref');

                expect($schemaRef)
                    ->toBe('#/components/schemas/ApiError', "{$method} {$path} missing default ApiError response");

                $checked++;
            }
        }

        expect($checked)->toBeGreaterThan(0);
    });

    it('OpenAPI document mentions seating capacity for max_vacancies', function () {
        $response = $this->getJson('/api/external/docs.json');

        $response->assertSuccessful();
        expect($response->getContent())->toContain('Seating capacity');
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
