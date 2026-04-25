/**
 * Get a cookie value by name
 */
export function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(^|;\\s*)${name}=([^;]*)`));
    return match ? decodeURIComponent(match[2]) : null;
}

/**
 * Fetch CSRF cookie from Sanctum endpoint
 */
export async function getCsrfCookie(): Promise<void> {
    const basePath = app.BASE_PATH || "";
    await fetch(`${basePath}/sanctum/csrf-cookie`, {
        credentials: "same-origin",
    });
}

/**
 * Build a URL query string from an object
 */
export function buildQueryString(
    params: Record<string, string | number | boolean | null | undefined>,
): string {
    const searchParams = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
        if (
            value !== undefined &&
            value !== null &&
            value !== "" &&
            value !== false
        ) {
            searchParams.append(key, String(value));
        }
    }
    const queryString = searchParams.toString();
    return queryString ? `?${queryString}` : "";
}

/**
 * Standard JSON error body returned by the Laravel API.
 */
export interface ApiErrorBody {
    error: string;
    message: string;
    errors?: Record<string, string[]>;
}

/**
 * Options for {@link createApiClient}.
 */
export interface CreateApiClientOptions {
    /**
     * Resolves the base URL for every request. A function (rather than a string)
     * lets callers defer reading `app.API_URL`, which is set on the global at
     * runtime by the Blade head script.
     */
    basePath: () => string;

    /**
     * Builds the error thrown when a response is not OK. Defaults to a generic
     * `Error` so callers without a custom error class still get a reasonable
     * thrown value.
     */
    errorFactory?: (body: ApiErrorBody, status: number) => Error;

    /**
     * Reads the XSRF token to forward as `X-XSRF-TOKEN`. Defaults to the
     * `XSRF-TOKEN` cookie. Exposed primarily so tests can replace the
     * cookie lookup without monkey-patching globals.
     */
    readXsrfToken?: () => string | null;
}

/**
 * Create a typed `fetch` wrapper that:
 *
 * - Prepends a base URL.
 * - Sets `Accept: application/json` (and `Content-Type: application/json`
 *   except for `FormData` bodies, where the browser sets the multipart
 *   boundary itself).
 * - Forwards the `XSRF-TOKEN` cookie as the `X-XSRF-TOKEN` header.
 * - Throws a customizable error when the response is not OK.
 */
export function createApiClient(opts: CreateApiClientOptions) {
    const errorFactory =
        opts.errorFactory ??
        ((body: ApiErrorBody) => new Error(body.message));
    const readXsrfToken = opts.readXsrfToken ?? (() => getCookie("XSRF-TOKEN"));

    return async function fetchJson<T>(
        endpoint: string,
        options?: RequestInit,
    ): Promise<T> {
        const headers: Record<string, string> = {
            Accept: "application/json",
        };

        // Skip Content-Type for FormData (browser sets it with boundary automatically)
        if (!(options?.body instanceof FormData)) {
            headers["Content-Type"] = "application/json";
        }

        // Include XSRF token for non-GET requests
        const xsrfToken = readXsrfToken();
        if (xsrfToken) {
            headers["X-XSRF-TOKEN"] = xsrfToken;
        }

        const response = await fetch(`${opts.basePath()}${endpoint}`, {
            headers,
            credentials: "same-origin",
            ...options,
        });

        if (!response.ok) {
            const data: ApiErrorBody = await response.json().catch(() => ({
                error: "unknown_error",
                message: response.statusText,
            }));
            throw errorFactory(data, response.status);
        }

        return response.json();
    };
}
