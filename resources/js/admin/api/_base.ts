import {
    buildQueryString,
    createApiClient,
    getCookie,
    getCsrfCookie,
} from "@shared/api/httpUtils";

export class AdminApiError extends Error {
    constructor(
        public readonly code: string,
        message: string,
        public readonly status: number,
        public readonly errors?: Record<string, string[]>,
    ) {
        super(message);
        this.name = "AdminApiError";
    }
}

export { buildQueryString, getCsrfCookie };

export const fetchAdminApi = createApiClient({
    basePath: () => `${app.API_URL}/admin`,
    errorFactory: (body, status) =>
        new AdminApiError(body.error, body.message, status, body.errors),
    readXsrfToken: () => getCookie("XSRF-TOKEN"),
});
