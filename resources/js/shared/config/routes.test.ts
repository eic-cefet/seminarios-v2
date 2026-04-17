import { describe, it, expect } from "vitest";
import { ROUTES } from "./routes";

describe("ROUTES.SYSTEM", () => {
    it("exposes static system routes", () => {
        expect(ROUTES.SYSTEM.HOME).toBe("/");
        expect(ROUTES.SYSTEM.LOGIN).toBe("/login");
        expect(ROUTES.SYSTEM.REGISTER).toBe("/cadastro");
        expect(ROUTES.SYSTEM.FORGOT_PASSWORD).toBe("/recuperar-senha");
        expect(ROUTES.SYSTEM.RESET_PASSWORD).toBe("/redefinir-senha");
        expect(ROUTES.SYSTEM.PROFILE).toBe("/perfil");
        expect(ROUTES.SYSTEM.CERTIFICATES).toBe("/certificados");
        expect(ROUTES.SYSTEM.EVALUATIONS).toBe("/avaliar");
        expect(ROUTES.SYSTEM.PRESENTATIONS).toBe("/apresentacoes");
        expect(ROUTES.SYSTEM.SUBJECTS).toBe("/topicos");
        expect(ROUTES.SYSTEM.WORKSHOPS).toBe("/workshops");
        expect(ROUTES.SYSTEM.BUG_REPORT).toBe("/reportar-bug");
        expect(ROUTES.SYSTEM.AUTH_CALLBACK).toBe("/auth/callback");
    });

    it("builds dynamic system routes", () => {
        expect(ROUTES.SYSTEM.SEMINAR_DETAILS("my-seminar")).toBe(
            "/seminario/my-seminar",
        );
        expect(ROUTES.SYSTEM.SEMINAR_CALENDAR_ICS("my-seminar")).toBe(
            "/seminario/my-seminar/calendar.ics",
        );
        expect(ROUTES.SYSTEM.SUBJECT_DETAILS("ai")).toBe("/topico/ai");
        expect(ROUTES.SYSTEM.WORKSHOP_DETAILS("intro")).toBe("/workshop/intro");
    });

    it("exposes route patterns used in Route definitions", () => {
        expect(ROUTES.SYSTEM.SEMINAR_DETAILS_PATTERN).toBe("/seminario/:slug");
        expect(ROUTES.SYSTEM.SUBJECT_DETAILS_PATTERN).toBe("/topico/:slug");
        expect(ROUTES.SYSTEM.WORKSHOP_DETAILS_PATTERN).toBe("/workshop/:slug");
        expect(ROUTES.SYSTEM.PRESENCE_PATTERN).toBe("/p/:uuid");
    });
});

describe("ROUTES.ADMIN", () => {
    it("exposes static admin routes relative to /admin basename", () => {
        expect(ROUTES.ADMIN.DASHBOARD).toBe("/");
        expect(ROUTES.ADMIN.USERS).toBe("/users");
        expect(ROUTES.ADMIN.LOCATIONS).toBe("/locations");
        expect(ROUTES.ADMIN.SUBJECTS).toBe("/subjects");
        expect(ROUTES.ADMIN.SEMINARS).toBe("/seminars");
        expect(ROUTES.ADMIN.SEMINAR_NEW).toBe("/seminars/new");
        expect(ROUTES.ADMIN.WORKSHOPS).toBe("/workshops");
        expect(ROUTES.ADMIN.REGISTRATIONS).toBe("/registrations");
        expect(ROUTES.ADMIN.API_TOKENS).toBe("/api-tokens");
        expect(ROUTES.ADMIN.REPORTS_SEMESTRAL).toBe("/reports/semestral");
        expect(ROUTES.ADMIN.REPORTS_FEEDBACK).toBe("/reports/feedback");
        expect(ROUTES.ADMIN.REPORTS_AUDIT_LOGS).toBe("/reports/audit-logs");
    });

    it("builds dynamic admin routes", () => {
        expect(ROUTES.ADMIN.SEMINAR_EDIT(42)).toBe("/seminars/42/edit");
        expect(ROUTES.ADMIN.SEMINAR_EDIT("abc")).toBe("/seminars/abc/edit");
    });

    it("exposes admin edit pattern", () => {
        expect(ROUTES.ADMIN.SEMINAR_EDIT_PATTERN).toBe("/seminars/:id/edit");
    });
});
