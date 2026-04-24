/**
 * Centralized route constants for both SPAs.
 *
 * SYSTEM routes are absolute paths (system SPA uses the project root as its
 * router basename). ADMIN routes are relative to the admin basename
 * (`<ROUTER_BASE>/admin`), so `ROUTES.ADMIN.SEMINARS` is `/seminars`, not
 * `/admin/seminars`.
 *
 * Dynamic segments are exposed as builder functions (`SEMINAR_DETAILS(slug)`)
 * alongside the raw pattern (`SEMINAR_DETAILS_PATTERN`) used when defining
 * the route in `<Route path=... />`.
 */
export const ROUTES = {
    SYSTEM: {
        HOME: "/",
        LOGIN: "/login",
        TWO_FACTOR_CHALLENGE: "/login/two-factor",
        REGISTER: "/cadastro",
        FORGOT_PASSWORD: "/recuperar-senha",
        RESET_PASSWORD: "/redefinir-senha",
        AUTH_CALLBACK: "/auth/callback",
        PROFILE: "/perfil",
        CERTIFICATES: "/certificados",
        ALERT_PREFERENCES: "/perfil/preferencias/alertas",
        EVALUATIONS: "/avaliar",
        BUG_REPORT: "/reportar-bug",
        PRESENTATIONS: "/apresentacoes",
        SUBJECTS: "/topicos",
        SUBJECT_DETAILS_PATTERN: "/topico/:slug",
        SUBJECT_DETAILS: (slug: string) => `/topico/${slug}`,
        SEMINAR_DETAILS_PATTERN: "/seminario/:slug",
        SEMINAR_DETAILS: (slug: string) => `/seminario/${slug}`,
        SEMINAR_CALENDAR_ICS: (slug: string) => `/seminario/${slug}/calendar.ics`,
        WORKSHOPS: "/workshops",
        WORKSHOP_DETAILS_PATTERN: "/workshop/:slug",
        WORKSHOP_DETAILS: (slug: string) => `/workshop/${slug}`,
        PRESENCE_PATTERN: "/p/:uuid",
        PRESENCE: (uuid: string) => `/p/${uuid}`,
        DELETION_CONFIRM_PATTERN: "/confirmar-exclusao/:token",
        DELETION_CONFIRM: (token: string) => `/confirmar-exclusao/${token}`,
        PRIVACY_POLICY: "/politica-de-privacidade",
        TERMS: "/termos-de-uso",
        DATA_RIGHTS: "/direitos-de-dados",
        COOKIE_PREFERENCES: "/preferencias-de-cookies",
    },
    ADMIN: {
        DASHBOARD: "/",
        USERS: "/users",
        LOCATIONS: "/locations",
        SUBJECTS: "/subjects",
        SEMINARS: "/seminars",
        SEMINAR_NEW: "/seminars/new",
        SEMINAR_EDIT_PATTERN: "/seminars/:id/edit",
        SEMINAR_EDIT: (id: number | string) => `/seminars/${id}/edit`,
        WORKSHOPS: "/workshops",
        REGISTRATIONS: "/registrations",
        API_TOKENS: "/api-tokens",
        REPORTS_SEMESTRAL: "/reports/semestral",
        REPORTS_FEEDBACK: "/reports/feedback",
        REPORTS_AUDIT_LOGS: "/reports/audit-logs",
    },
} as const;
