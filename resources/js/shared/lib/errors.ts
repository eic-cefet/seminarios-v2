import { ApiRequestError } from "@shared/api/client";

export const API_ERROR_MESSAGES: Record<string, string> = {
    // Auth errors
    unauthenticated: "Você precisa estar logado para acessar este recurso.",
    mismatched_credentials: "E-mail ou senha incorretos.",
    forbidden: "Você não tem permissão para acessar este recurso.",
    email_not_verified: "Verifique seu e-mail antes de continuar.",
    invalid_token: "Link inválido ou expirado. Solicite um novo.",

    // Rate limiting
    rate_limited: "Muitas tentativas. Aguarde um momento e tente novamente.",

    // Registration errors
    already_registered: "Você já está inscrito neste seminário.",
    not_registered: "Você não está inscrito neste seminário.",
    seminar_expired: "Este seminário já foi realizado.",
    seminar_full: "Este seminário atingiu sua capacidade máxima.",
    conflict: "Não foi possível completar a operação devido a um conflito.",

    // Validation
    validation_error: "Verifique os dados informados.",

    // Generic
    not_found: "Recurso não encontrado.",
    server_error: "Erro interno. Tente novamente mais tarde.",
    unknown_error: "Ocorreu um erro. Tente novamente.",
};

export function getErrorMessage(error: unknown): string {
    if (error instanceof ApiRequestError) {
        return (
            API_ERROR_MESSAGES[error.code] ||
            error.message ||
            API_ERROR_MESSAGES.unknown_error
        );
    }

    if (error instanceof Error) {
        return error.message;
    }

    return API_ERROR_MESSAGES.unknown_error;
}

export function getFieldErrors(
    error: unknown,
): Record<string, string> | undefined {
    if (error instanceof ApiRequestError && error.errors) {
        const fieldErrors: Record<string, string> = {};
        for (const [field, messages] of Object.entries(error.errors)) {
            fieldErrors[field] = messages[0];
        }
        return fieldErrors;
    }
    return undefined;
}
