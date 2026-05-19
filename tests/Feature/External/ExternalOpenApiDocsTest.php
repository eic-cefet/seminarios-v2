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
        expect($tags)->toContain('Presence Link');
        expect($tags)->not->toContain('ExternalSeminar');
        expect($tags)->not->toContain('ExternalLocation');
        expect($tags)->not->toContain('ExternalSeminarType');
        expect($tags)->not->toContain('ExternalUser');
        expect($tags)->not->toContain('ExternalSpeakerData');
        expect($tags)->not->toContain('ExternalWorkshop');
        expect($tags)->not->toContain('ExternalPresenceLink');
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
        expect($schemas)->toContain('ExternalPresenceLinkResource');
        expect($schemas)->toContain('ExternalPresenceLinkUpdateRequest');
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

    it('Stoplight info.description includes the Getting Started content', function () {
        $response = $this->getJson('/api/external/docs.json');
        $response->assertSuccessful();
        $description = data_get($response->json(), 'info.description', '');
        expect($description)->toContain('Getting Started')
            ->and($description)->toContain('Authentication')
            ->and($description)->toContain('Pagination')
            ->and($description)->toContain('Sparse Fieldsets')
            ->and($description)->toContain('Conditional Requests')
            ->and($description)->toContain('ETag')
            ->and($description)->toContain('Errors');
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

    it('documents the presence-link endpoints with the expected verbs and operation IDs', function () {
        $doc = $this->getJson('/api/external/docs.json')->json();

        $path = data_get($doc, 'paths./v1/seminars/{seminar}/presence-link');
        expect($path)->not->toBeNull('/v1/seminars/{seminar}/presence-link missing from OpenAPI doc');

        // Scramble emits only the first verb from Route::match (`put` here),
        // same as every other external update route in this codebase. The
        // route still accepts both PATCH and PUT at runtime — covered by the
        // controller test "accepts PUT in addition to PATCH".
        expect($path)->toHaveKey('get');
        expect($path)->toHaveKey('post');
        expect($path)->toHaveKey('put');

        expect(data_get($path, 'get.operationId'))->toBe('seminars.presence-link.show');
        expect(data_get($path, 'post.operationId'))->toBe('seminars.presence-link.store');
        expect(data_get($path, 'put.operationId'))->toBe('seminars.presence-link.update');

        // GET documents the include query parameter.
        $getParams = collect(data_get($path, 'get.parameters', []))->pluck('name')->all();
        expect($getParams)->toContain('include');

        // PUT (update) documents the body fields.
        $putBody = data_get($path, 'put.requestBody.content.application/json.schema');
        expect($putBody)->not->toBeNull();
        $serialized = json_encode($putBody);
        expect($serialized)->toContain('active');
        expect($serialized)->toContain('expires_at');

        // Every operation carries the Presence Link tag.
        expect(data_get($path, 'get.tags'))->toContain('Presence Link');
        expect(data_get($path, 'post.tags'))->toContain('Presence Link');
        expect(data_get($path, 'put.tags'))->toContain('Presence Link');
    });
});
