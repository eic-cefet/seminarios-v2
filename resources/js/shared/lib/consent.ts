export const CONSENT_STORAGE_KEY = "lgpd.cookie_consent.v1";
export const ANON_ID_STORAGE_KEY = "lgpd.anonymous_id.v1";

export type CookieCategoryId = "essential" | "functional" | "analytics";

export interface CookieCategory {
    id: CookieCategoryId;
    label: string;
    description: string;
    required: boolean;
}

export const COOKIE_CATEGORIES: readonly CookieCategory[] = [
    {
        id: "essential",
        label: "Essenciais",
        description:
            "Cookies necessários para autenticação, sessão e proteção CSRF. Sem eles, o site não funciona.",
        required: true,
    },
    {
        id: "functional",
        label: "Funcionais",
        description:
            "Guardam preferências de interface (como tema e idioma) para melhorar sua experiência.",
        required: false,
    },
    {
        id: "analytics",
        label: "Análise",
        description:
            "Ajudam a entender como as pessoas usam o sistema, permitindo melhorias. Nenhum dado é compartilhado com terceiros para fins de publicidade.",
        required: false,
    },
] as const;

export interface CookieConsentState {
    essential: true;
    functional: boolean;
    analytics: boolean;
    version: string;
    decided_at: string;
}

export function loadCookieConsent(): CookieConsentState | null {
    try {
        const raw = localStorage.getItem(CONSENT_STORAGE_KEY);
        if (!raw) return null;
        return JSON.parse(raw) as CookieConsentState;
    } catch {
        return null;
    }
}

export function saveCookieConsent(state: CookieConsentState): void {
    localStorage.setItem(CONSENT_STORAGE_KEY, JSON.stringify(state));
}

export function clearCookieConsent(): void {
    localStorage.removeItem(CONSENT_STORAGE_KEY);
}

export function ensureAnonymousId(): string {
    const existing = localStorage.getItem(ANON_ID_STORAGE_KEY);
    if (existing) return existing;
    const id = `anon-${crypto.randomUUID()}`;
    localStorage.setItem(ANON_ID_STORAGE_KEY, id);
    return id;
}
