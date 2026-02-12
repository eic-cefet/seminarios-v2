<?php

use App\Exceptions\ApiException;

describe('ApiException', function () {
    it('creates exception with error code', function () {
        $exception = new ApiException('test_error', 'Test message', 400);

        expect($exception->errorCode)->toBe('test_error');
        expect($exception->getMessage())->toBe('Test message');
        expect($exception->statusCode)->toBe(400);
    });

    it('uses error code as message when message is empty', function () {
        $exception = new ApiException('default_error');

        expect($exception->getMessage())->toBe('default_error');
    });

    it('renders json response without errors', function () {
        $exception = new ApiException('test_error', 'Test message', 400);
        $response = $exception->render();

        expect($response->getStatusCode())->toBe(400);
        $data = json_decode($response->getContent(), true);
        expect($data['error'])->toBe('test_error');
        expect($data['message'])->toBe('Test message');
        expect($data)->not->toHaveKey('errors');
    });

    it('renders json response with errors', function () {
        $exception = new ApiException('validation', 'Invalid data', 422, ['field' => ['error']]);
        $response = $exception->render();

        $data = json_decode($response->getContent(), true);
        expect($data['errors'])->toBe(['field' => ['error']]);
    });

    it('creates unauthenticated exception', function () {
        $exception = ApiException::unauthenticated();

        expect($exception->errorCode)->toBe('unauthenticated');
        expect($exception->statusCode)->toBe(401);
    });

    it('creates mismatched credentials exception', function () {
        $exception = ApiException::mismatchedCredentials();

        expect($exception->errorCode)->toBe('mismatched_credentials');
        expect($exception->statusCode)->toBe(401);
    });

    it('creates forbidden exception', function () {
        $exception = ApiException::forbidden();

        expect($exception->errorCode)->toBe('forbidden');
        expect($exception->statusCode)->toBe(403);
    });

    it('creates forbidden exception with custom message', function () {
        $exception = ApiException::forbidden('Custom forbidden');

        expect($exception->getMessage())->toBe('Custom forbidden');
    });

    it('creates conflict exception', function () {
        $exception = ApiException::conflict();

        expect($exception->errorCode)->toBe('conflict');
        expect($exception->statusCode)->toBe(409);
    });

    it('creates not found exception', function () {
        $exception = ApiException::notFound('User');

        expect($exception->errorCode)->toBe('not_found');
        expect($exception->statusCode)->toBe(404);
        expect($exception->getMessage())->toBe('User não encontrado');
    });

    it('creates rate limited exception', function () {
        $exception = ApiException::rateLimited();

        expect($exception->errorCode)->toBe('rate_limited');
        expect($exception->statusCode)->toBe(429);
    });

    it('creates validation exception', function () {
        $errors = ['email' => ['Email is required']];
        $exception = ApiException::validation($errors);

        expect($exception->errorCode)->toBe('validation_error');
        expect($exception->statusCode)->toBe(422);
        expect($exception->errors)->toBe($errors);
    });

    it('creates invalid token exception', function () {
        $exception = ApiException::invalidToken();

        expect($exception->errorCode)->toBe('invalid_token');
        expect($exception->statusCode)->toBe(400);
    });

    it('creates server error exception', function () {
        $exception = ApiException::serverError();

        expect($exception->errorCode)->toBe('server_error');
        expect($exception->statusCode)->toBe(500);
    });

    it('creates already registered exception', function () {
        $exception = ApiException::alreadyRegistered();

        expect($exception->errorCode)->toBe('already_registered');
        expect($exception->statusCode)->toBe(409);
    });

    it('creates not registered exception', function () {
        $exception = ApiException::notRegistered();

        expect($exception->errorCode)->toBe('not_registered');
        expect($exception->statusCode)->toBe(400);
    });

    it('creates seminar expired exception', function () {
        $exception = ApiException::seminarExpired();

        expect($exception->errorCode)->toBe('seminar_expired');
        expect($exception->statusCode)->toBe(400);
    });

    it('creates cannot merge subjects exception', function () {
        $exception = ApiException::cannotMergeSubjects();

        expect($exception->errorCode)->toBe('cannot_merge_subjects');
        expect($exception->statusCode)->toBe(400);
    });

    it('creates subject in use exception', function () {
        $exception = ApiException::subjectInUse();

        expect($exception->errorCode)->toBe('subject_in_use');
        expect($exception->statusCode)->toBe(409);
    });

    it('creates workshop in use exception', function () {
        $exception = ApiException::workshopInUse();

        expect($exception->errorCode)->toBe('workshop_in_use');
        expect($exception->statusCode)->toBe(409);
    });

    it('creates cannot delete self exception', function () {
        $exception = ApiException::cannotDeleteSelf();

        expect($exception->errorCode)->toBe('cannot_delete_self');
        expect($exception->statusCode)->toBe(400);
    });

    it('creates seminar full exception', function () {
        $exception = ApiException::seminarFull();

        expect($exception->errorCode)->toBe('seminar_full');
        expect($exception->statusCode)->toBe(409);
    });
});
