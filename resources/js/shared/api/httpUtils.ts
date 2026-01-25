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
    params: Record<string, string | number | boolean | undefined>,
): string {
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== "") {
            searchParams.append(key, String(value));
        }
    });
    const queryString = searchParams.toString();
    return queryString ? `?${queryString}` : "";
}
