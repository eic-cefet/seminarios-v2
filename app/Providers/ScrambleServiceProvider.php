<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Illuminate\Support\ServiceProvider;

class ScrambleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            $this->registerApiErrorSchema($openApi);
            $this->attachDefaultErrorResponseToExternalOperations($openApi);
        });
    }

    /**
     * Publish the canonical ApiError schema referenced by all external endpoints.
     *
     * Mirrors the envelope thrown by App\Exceptions\ApiException:
     * { error: string, message: string, errors?: object|null }.
     */
    private function registerApiErrorSchema(OpenApi $openApi): void
    {
        $errorsType = (new ObjectType)
            ->setDescription('Field-level validation errors. Present only on 422 validation failures.')
            ->nullable(true);

        $apiError = (new ObjectType)
            ->addProperty(
                'error',
                (new StringType)->setDescription('Stable machine-readable error code (e.g. validation_error, forbidden, conflict).'),
            )
            ->addProperty(
                'message',
                (new StringType)->setDescription('Human-readable description of the failure.'),
            )
            ->addProperty('errors', $errorsType)
            ->setRequired(['error', 'message']);

        $openApi->components->addSchema('ApiError', Schema::fromType($apiError));
    }

    /**
     * Attach a default response that references the ApiError schema on every
     * external operation, so integrators see the real error shape instead of
     * Scramble's generic "default response".
     */
    private function attachDefaultErrorResponseToExternalOperations(OpenApi $openApi): void
    {
        $reference = $openApi->components->getSchemaReference('ApiError');

        foreach ($openApi->paths as $path) {
            if (! str_starts_with($path->path, 'v1/')) {
                continue;
            }

            foreach ($path->operations as $operation) {
                if ($this->operationAlreadyDeclaresDefaultResponse($operation->responses)) {
                    continue;
                }

                $operation->responses[] = Response::make('default')
                    ->setDescription('Error response. See `ApiError` schema for the envelope.')
                    ->setContent('application/json', Schema::fromType($reference));
            }
        }
    }

    /**
     * @param  array<int, mixed>|null  $responses
     */
    private function operationAlreadyDeclaresDefaultResponse(?array $responses): bool
    {
        if ($responses === null) {
            return false;
        }

        foreach ($responses as $response) {
            if ($response instanceof Response && ($response->code === 'default' || $response->code === null)) {
                return true;
            }
        }

        return false;
    }
}
