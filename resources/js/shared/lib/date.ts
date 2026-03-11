import { format, isPast as dfIsPast, isToday as dfIsToday } from "date-fns";
import { toZonedTime } from "date-fns-tz";
import { ptBR } from "date-fns/locale";

const TIMEZONE = "America/Sao_Paulo";

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
