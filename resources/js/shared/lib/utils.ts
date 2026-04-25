import { clsx, type ClassValue } from "clsx";
import { format, isPast as dfIsPast, isToday as dfIsToday } from "date-fns";
import { toZonedTime } from "date-fns-tz";
import { ptBR } from "date-fns/locale";
import { twMerge } from "tailwind-merge";

const TIMEZONE = "America/Sao_Paulo";

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function containsHTML(text: string): boolean {
    return /<[a-z][\s\S]*>/i.test(text);
}

/**
 * Parse a date string or Date into a zoned Date in America/Sao_Paulo.
 */
export function toSaoPaulo(date: string | Date): Date {
    const d = typeof date === "string" ? new Date(date) : date;
    return toZonedTime(d, TIMEZONE);
}

export function formatDate(date: string | Date): string {
    return format(toSaoPaulo(date), "dd/MM/yyyy", { locale: ptBR });
}

export function formatDateTime(date: string | Date): string {
    return format(toSaoPaulo(date), "dd/MM/yyyy, HH:mm", { locale: ptBR });
}

export function formatDateTimeLong(date: string | Date): string {
    return format(toSaoPaulo(date), "dd 'de' MMMM 'de' yyyy, HH:mm", {
        locale: ptBR,
    });
}

export function formatDurationMinutes(minutes: number): string {
    const totalMinutes = Math.max(Math.trunc(minutes), 0);
    const hours = Math.floor(totalMinutes / 60);
    const remainingMinutes = totalMinutes % 60;

    if (hours > 0 && remainingMinutes > 0) {
        return `${hours}h ${remainingMinutes}min`;
    }

    if (hours > 0) {
        return `${hours}h`;
    }

    return `${remainingMinutes}min`;
}

export function isExpired(date: string | Date): boolean {
    const d = typeof date === "string" ? new Date(date) : date;
    return dfIsPast(d);
}

export function isToday(date: string | Date): boolean {
    return dfIsToday(toSaoPaulo(date));
}

export function nowInSaoPaulo(): Date {
    return toZonedTime(new Date(), TIMEZONE);
}

export function getYear(): number {
    return nowInSaoPaulo().getFullYear();
}

export function getSemester(): { year: number; semester: 1 | 2 } {
    const now = nowInSaoPaulo();
    const month = now.getMonth() + 1;
    return { year: now.getFullYear(), semester: month <= 6 ? 1 : 2 };
}

/**
 * Convert an ISO datetime string to the `datetime-local` input format
 * (YYYY-MM-DDTHH:mm) in America/Sao_Paulo timezone.
 */
export function toDatetimeLocal(date: string | Date): string {
    return format(toSaoPaulo(date), "yyyy-MM-dd'T'HH:mm");
}

/**
 * Sort comparator for date strings (descending — newest first).
 */
export function compareDateDesc(a: string, b: string): number {
    return new Date(b).getTime() - new Date(a).getTime();
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
