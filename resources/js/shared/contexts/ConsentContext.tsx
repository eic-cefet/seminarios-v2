import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
    type ReactNode,
} from "react";
import { consentsApi, type ConsentType } from "@shared/api/client";
import {
    type CookieConsentState,
    ensureAnonymousId,
    loadCookieConsent,
    saveCookieConsent,
} from "@shared/lib/consent";

interface ConsentContextValue {
    state: CookieConsentState | null;
    hasDecided: boolean;
    acceptAll: () => Promise<void>;
    rejectAll: () => Promise<void>;
    save: (preferences: {
        functional: boolean;
        analytics: boolean;
    }) => Promise<void>;
    reopen: () => void;
    isOpen: boolean;
    close: () => void;
}

const ConsentContext = createContext<ConsentContextValue | null>(null);

const CONSENT_VERSION = "1.0";

interface ProviderProps {
    children: ReactNode;
}

export function ConsentProvider({ children }: ProviderProps) {
    const [state, setState] = useState<CookieConsentState | null>(null);
    const [isOpen, setIsOpen] = useState(false);

    useEffect(() => {
        const stored = loadCookieConsent();
        setState(stored);
        setIsOpen(stored === null);
    }, []);

    const syncToApi = useCallback(async (next: CookieConsentState) => {
        const anonymousId = ensureAnonymousId();
        const entries: Array<{ type: ConsentType; granted: boolean }> = [
            { type: "cookies_functional", granted: next.functional },
            { type: "cookies_analytics", granted: next.analytics },
        ];
        await Promise.all(
            entries.map((e) =>
                consentsApi
                    .record({
                        type: e.type,
                        granted: e.granted,
                        version: CONSENT_VERSION,
                        anonymous_id: anonymousId,
                    })
                    .catch(() => undefined),
            ),
        );
    }, []);

    const persist = useCallback(
        async (preferences: { functional: boolean; analytics: boolean }) => {
            const next: CookieConsentState = {
                essential: true,
                functional: preferences.functional,
                analytics: preferences.analytics,
                version: CONSENT_VERSION,
                decided_at: new Date().toISOString(),
            };
            saveCookieConsent(next);
            setState(next);
            setIsOpen(false);
            await syncToApi(next);
        },
        [syncToApi],
    );

    const acceptAll = useCallback(
        () => persist({ functional: true, analytics: true }),
        [persist],
    );
    const rejectAll = useCallback(
        () => persist({ functional: false, analytics: false }),
        [persist],
    );
    const save = persist;
    const reopen = useCallback(() => setIsOpen(true), []);
    const close = useCallback(() => setIsOpen(false), []);

    const value = useMemo<ConsentContextValue>(
        () => ({
            state,
            hasDecided: state !== null,
            acceptAll,
            rejectAll,
            save,
            reopen,
            isOpen,
            close,
        }),
        [state, acceptAll, rejectAll, save, reopen, isOpen, close],
    );

    return (
        <ConsentContext.Provider value={value}>
            {children}
        </ConsentContext.Provider>
    );
}

export function useConsent(): ConsentContextValue {
    const ctx = useContext(ConsentContext);
    if (!ctx) {
        throw new Error("useConsent must be used inside ConsentProvider");
    }
    return ctx;
}
