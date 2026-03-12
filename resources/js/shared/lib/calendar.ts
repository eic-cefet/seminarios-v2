import { formatInTimeZone } from "date-fns-tz";
import { buildUrl } from "./utils";

const DEFAULT_DURATION_MS = 60 * 60 * 1000;

export interface CalendarEventDetails {
    title: string;
    startsAt: string | Date;
    endsAt?: string | Date;
    description?: string | null;
    location?: string | null;
    roomLink?: string | null;
    eventPath?: string | null;
    downloadPath: string;
}

export interface CalendarLinks {
    google: string;
    outlook: string;
    ics: string;
}

function toDate(value: string | Date): Date {
    return value instanceof Date ? value : new Date(value);
}

function resolveEndDate(startsAt: string | Date, endsAt?: string | Date): Date {
    if (endsAt) {
        return toDate(endsAt);
    }

    return new Date(toDate(startsAt).getTime() + DEFAULT_DURATION_MS);
}

function toAbsoluteAppUrl(path: string): string {
    const resolvedPath = buildUrl(path);

    if (typeof window === "undefined") {
        return resolvedPath;
    }

    return new URL(resolvedPath, window.location.origin).toString();
}

function buildDetails({
    description,
    roomLink,
    eventPath,
}: Pick<CalendarEventDetails, "description" | "roomLink" | "eventPath">): string | null {
    const parts = [description?.trim() ?? ""].filter(Boolean);

    if (roomLink?.trim()) {
        parts.push(`Link de acesso: ${roomLink.trim()}`);
    }

    if (eventPath?.trim()) {
        parts.push(`Pagina do evento: ${toAbsoluteAppUrl(eventPath.trim())}`);
    }

    return parts.length > 0 ? parts.join("\n\n") : null;
}

function toGoogleDate(value: string | Date): string {
    return formatInTimeZone(
        toDate(value),
        "UTC",
        "yyyyMMdd'T'HHmmss'Z'",
    );
}

function toOutlookDate(value: string | Date): string {
    return toDate(value).toISOString();
}

export function createCalendarLinks(
    event: CalendarEventDetails,
): CalendarLinks {
    const startsAt = toDate(event.startsAt);
    const endsAt = resolveEndDate(startsAt, event.endsAt);
    const details = buildDetails(event);
    const googleUrl = new URL("https://calendar.google.com/calendar/render");
    const outlookUrl = new URL(
        "https://outlook.office.com/calendar/deeplink/compose",
    );

    googleUrl.searchParams.set("action", "TEMPLATE");
    googleUrl.searchParams.set("text", event.title);
    googleUrl.searchParams.set(
        "dates",
        `${toGoogleDate(startsAt)}/${toGoogleDate(endsAt)}`,
    );
    if (details) {
        googleUrl.searchParams.set("details", details);
    }
    if (event.location?.trim()) {
        googleUrl.searchParams.set("location", event.location.trim());
    }

    outlookUrl.searchParams.set("rru", "addevent");
    outlookUrl.searchParams.set("subject", event.title);
    outlookUrl.searchParams.set("startdt", toOutlookDate(startsAt));
    outlookUrl.searchParams.set("enddt", toOutlookDate(endsAt));
    if (details) {
        outlookUrl.searchParams.set("body", details);
    }
    if (event.location?.trim()) {
        outlookUrl.searchParams.set("location", event.location.trim());
    }

    return {
        google: googleUrl.toString(),
        outlook: outlookUrl.toString(),
        ics: buildUrl(event.downloadPath),
    };
}
