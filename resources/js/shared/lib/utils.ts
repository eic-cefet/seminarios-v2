import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export {
    formatDate,
    formatDateTime,
    formatDateTimeLong,
    isExpired,
} from "./date";

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function containsHTML(text: string): boolean {
    return /<[a-z][\s\S]*>/i.test(text);
}

export function stripHtml(text: string): string {
    return text.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
}

export function truncateText(text: string, maxLength: number): string {
    if (text.length <= maxLength) {
        return text;
    }

    return `${text.slice(0, maxLength - 1).trimEnd()}…`;
}

/**
 * Validates that a redirect path is a safe relative path (not an open redirect).
 * Rejects protocol-relative URLs (//evil.com), backslash-relative URLs (/\evil.com),
 * absolute URLs, and non-string values.
 */
export function isSafeRedirect(path: unknown): path is string {
    return (
        typeof path === "string" &&
        path.startsWith("/") &&
        !path.startsWith("//") &&
        !path.startsWith("/\\")
    );
}

/**
 * Build a full URL path with the app's base path prefix.
 * Use this for window.location redirects and other non-React-Router navigation.
 *
 * @example
 * buildUrl('/login') // Returns '/seminarios/login' or '/login' depending on deployment
 * buildUrl(`/auth/${provider}`) // Returns '/seminarios/auth/google' etc.
 */
export function buildUrl(path: string): string {
    const base = app.ROUTER_BASE || "";
    if (base && path.startsWith("/")) {
        return `${base}${path}`;
    }
    return path;
}

export function buildAbsoluteUrl(path: string): string {
    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    const configuredBase = app.BASE_PATH?.trim();
    const base =
        configuredBase && configuredBase.length > 0
            ? configuredBase.replace(/\/$/, "")
            : window.location.origin;

    if (!path.startsWith("/")) {
        return `${base}/${path}`;
    }

    return `${base}${path}`;
}
