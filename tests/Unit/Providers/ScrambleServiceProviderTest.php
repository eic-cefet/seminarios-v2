<?php

use App\Providers\ScrambleServiceProvider;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Path;
use Dedoc\Scramble\Support\Generator\Response;

/**
 * Helper that invokes the private `operationAlreadyDeclaresDefaultResponse`
 * method so each branch (null responses, 'default'-coded, null-coded, no
 * match) can be exercised directly.
 */
function callAlreadyDeclaresDefault(?array $responses): bool
{
    $provider = new ScrambleServiceProvider(app());
    $reflection = new ReflectionMethod($provider, 'operationAlreadyDeclaresDefaultResponse');

    return $reflection->invoke($provider, $responses);
}

it('returns false when responses array is null', function () {
    expect(callAlreadyDeclaresDefault(null))->toBeFalse();
});

it('returns true when an existing response uses the default code', function () {
    $existing = new Response('default');

    expect(callAlreadyDeclaresDefault([$existing]))->toBeTrue();
});

it('returns true when an existing response has a null code', function () {
    $existing = new Response(null);

    expect(callAlreadyDeclaresDefault([$existing]))->toBeTrue();
});

it('returns false when responses contain only specific status codes', function () {
    $ok = new Response(200);
    $created = new Response(201);

    expect(callAlreadyDeclaresDefault([$ok, $created]))->toBeFalse();
});

it('skips operations on /v1/ paths that already declare a default response', function () {
    $existingDefault = new Response('default');
    $existingDefault->setDescription('Pre-existing default kept by the provider.');

    $operation = new Operation('get');
    $operation->responses[] = $existingDefault;

    $path = new Path('v1/already-defaulted');
    $path->operations['get'] = $operation;

    $openApi = new OpenApi('3.1.0');
    $openApi->setComponents(new Components);
    $openApi->paths = [$path];

    $provider = new ScrambleServiceProvider(app());
    (new ReflectionMethod($provider, 'registerApiErrorSchema'))->invoke($provider, $openApi);
    (new ReflectionMethod($provider, 'attachDefaultErrorResponseToExternalOperations'))->invoke($provider, $openApi);

    expect($operation->responses)->toHaveCount(1)
        ->and($operation->responses[0])->toBe($existingDefault);
});

it('ignores paths that are not under the /v1/ prefix', function () {
    $operation = new Operation('get');

    $path = new Path('admin/v1/something');
    $path->operations['get'] = $operation;

    $openApi = new OpenApi('3.1.0');
    $openApi->setComponents(new Components);
    $openApi->paths = [$path];

    $provider = new ScrambleServiceProvider(app());
    (new ReflectionMethod($provider, 'registerApiErrorSchema'))->invoke($provider, $openApi);
    (new ReflectionMethod($provider, 'attachDefaultErrorResponseToExternalOperations'))->invoke($provider, $openApi);

    expect($operation->responses)->toBeEmpty();
});
