import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatDate(date: string | Date): string {
    const d = typeof date === "string" ? new Date(date) : date;
    return d.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
}

export function formatDateTime(date: string | Date): string {
    const d = typeof date === "string" ? new Date(date) : date;
    return d.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

export function isExpired(date: string | Date): boolean {
    const d = typeof date === "string" ? new Date(date) : date;
    return d < new Date();
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
