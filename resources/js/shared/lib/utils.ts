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
    // Avoid double slashes if path already starts with / and base ends with /
    if (base && path.startsWith("/")) {
        return `${base}${path}`;
    }
    return path;
}
