import { createCalendarLinks } from "./calendar";

describe("createCalendarLinks", () => {
    it("builds Google, Outlook, and ICS links with event details", () => {
        const links = createCalendarLinks({
            title: "Seminario de IA",
            startsAt: "2026-06-15T14:00:00Z",
            description: "Descricao do evento",
            location: "Sala 101",
            roomLink: "https://meet.example.com/ia",
            eventPath: "/seminario/seminario-ia",
            downloadPath: "/seminario/seminario-ia/calendar.ics",
        });

        const google = new URL(links.google);
        const outlook = new URL(links.outlook);

        expect(google.origin).toBe("https://calendar.google.com");
        expect(google.searchParams.get("text")).toBe("Seminario de IA");
        expect(google.searchParams.get("dates")).toBe(
            "20260615T140000Z/20260615T150000Z",
        );
        expect(google.searchParams.get("location")).toBe("Sala 101");
        expect(google.searchParams.get("details")).toContain(
            "Descricao do evento",
        );
        expect(google.searchParams.get("details")).toContain(
            "Link de acesso: https://meet.example.com/ia",
        );
        expect(google.searchParams.get("details")).toContain(
            `Pagina do evento: ${window.location.origin}/seminario/seminario-ia`,
        );

        expect(outlook.origin).toBe("https://outlook.office.com");
        expect(outlook.searchParams.get("subject")).toBe("Seminario de IA");
        expect(outlook.searchParams.get("startdt")).toBe(
            "2026-06-15T14:00:00.000Z",
        );
        expect(outlook.searchParams.get("enddt")).toBe(
            "2026-06-15T15:00:00.000Z",
        );
        expect(outlook.searchParams.get("location")).toBe("Sala 101");
        expect(outlook.searchParams.get("body")).toContain(
            "Descricao do evento",
        );

        expect(links.ics).toBe("/seminario/seminario-ia/calendar.ics");
    });

    it("uses a one-hour default duration and omits empty optional fields", () => {
        const links = createCalendarLinks({
            title: "Seminario sem extras",
            startsAt: "2026-07-10T10:30:00Z",
            downloadPath: "/seminario/simples/calendar.ics",
        });

        const google = new URL(links.google);
        const outlook = new URL(links.outlook);

        expect(google.searchParams.get("dates")).toBe(
            "20260710T103000Z/20260710T113000Z",
        );
        expect(google.searchParams.get("details")).toBeNull();
        expect(google.searchParams.get("location")).toBeNull();

        expect(outlook.searchParams.get("enddt")).toBe(
            "2026-07-10T11:30:00.000Z",
        );
        expect(outlook.searchParams.get("body")).toBeNull();
        expect(outlook.searchParams.get("location")).toBeNull();
    });
});
