<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message = '',
        public readonly int $statusCode = 400,
        public readonly array $errors = [],
    ) {
        parent::__construct($message ?: $errorCode);
    }

    public function render(): JsonResponse
    {
        $response = [
            'error' => $this->errorCode,
            'message' => $this->getMessage(),
        ];

        if (! empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        return response()->json($response, $this->statusCode);
    }

    // Common error factory methods
    public static function unauthenticated(): self
    {
        return new self('unauthenticated', 'Não autenticado', 401);
    }

    public static function mismatchedCredentials(): self
    {
        return new self('mismatched_credentials', 'Credenciais inválidas', 401);
    }

    public static function forbidden(): self
    {
        return new self('forbidden', 'Acesso negado', 403);
    }

    public static function notFound(string $resource = 'Recurso'): self
    {
        return new self('not_found', "{$resource} não encontrado", 404);
    }

    public static function rateLimited(): self
    {
        return new self('rate_limited', 'Muitas tentativas. Tente novamente em alguns minutos.', 429);
    }

    public static function validation(array $errors): self
    {
        return new self('validation_error', 'Dados inválidos', 422, $errors);
    }

    public static function invalidToken(): self
    {
        return new self('invalid_token', 'Token inválido ou expirado', 400);
    }

    public static function emailNotVerified(): self
    {
        return new self('email_not_verified', 'E-mail não verificado', 403);
    }

    public static function serverError(): self
    {
        return new self('server_error', 'Erro interno do servidor', 500);
    }

    public static function alreadyRegistered(): self
    {
        return new self('already_registered', 'Você já está inscrito neste seminário', 409);
    }

    public static function notRegistered(): self
    {
        return new self('not_registered', 'Você não está inscrito neste seminário', 400);
    }

    public static function seminarExpired(): self
    {
        return new self('seminar_expired', 'Este seminário já foi realizado', 400);
    }

    public static function cannotMergeSubjects(): self
    {
        return new self('cannot_merge_subjects', 'Não foi possível mesclar os assuntos', 400);
    }

    public static function subjectInUse(): self
    {
        return new self('subject_in_use', 'Este assunto está associado a seminários e não pode ser excluído', 409);
    }

    public static function cannotDeleteSelf(): self
    {
        return new self('cannot_delete_self', 'Você não pode excluir sua própria conta', 400);
    }
}
