import { useState } from "react";
import { Link } from "react-router-dom";
import { useConsent } from "@shared/contexts/ConsentContext";
import { COOKIE_CATEGORIES } from "@shared/lib/consent";
import { ROUTES } from "@shared/config/routes";
import { cn } from "@shared/lib/utils";

export function CookieConsentBanner() {
    const { isOpen, acceptAll, rejectAll, save, close } = useConsent();
    const [mode, setMode] = useState<"summary" | "customize">("summary");
    const [functional, setFunctional] = useState(false);
    const [analytics, setAnalytics] = useState(false);
    const [saving, setSaving] = useState(false);

    if (!isOpen) {
        return null;
    }

    const handleAll = async (accept: boolean) => {
        setSaving(true);
        try {
            if (accept) await acceptAll();
            else await rejectAll();
        } finally {
            setSaving(false);
        }
    };

    const handleCustom = async () => {
        setSaving(true);
        try {
            await save({ functional, analytics });
        } finally {
            setSaving(false);
        }
    };

    return (
        <div
            role="dialog"
            aria-label="Aviso de cookies"
            className="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 bg-white shadow-lg"
        >
            <div className="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
                {mode === "summary" ? (
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div className="text-sm text-gray-700">
                            <p className="font-semibold">Usamos cookies</p>
                            <p className="mt-1">
                                Utilizamos cookies essenciais para o
                                funcionamento do site e, com seu consentimento,
                                cookies funcionais e de análise para melhorar sua
                                experiência. Consulte nossa{" "}
                                <Link
                                    to={ROUTES.SYSTEM.PRIVACY_POLICY}
                                    className="underline text-primary-700"
                                >
                                    Política de Privacidade
                                </Link>
                                .
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2 md:flex-nowrap">
                            <button
                                type="button"
                                onClick={() => handleAll(false)}
                                disabled={saving}
                                className={cn(
                                    "rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50",
                                    /* v8 ignore next — cosmetic saving state styling */
                                    saving && "opacity-60",
                                )}
                            >
                                Rejeitar todos
                            </button>
                            <button
                                type="button"
                                onClick={() => setMode("customize")}
                                disabled={saving}
                                className={cn(
                                    "rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50",
                                    /* v8 ignore next — cosmetic saving state styling */
                                    saving && "opacity-60",
                                )}
                            >
                                Personalizar
                            </button>
                            <button
                                type="button"
                                onClick={() => handleAll(true)}
                                disabled={saving}
                                className={cn(
                                    "rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700",
                                    /* v8 ignore next — cosmetic saving state styling */
                                    saving && "opacity-60",
                                )}
                            >
                                Aceitar todos
                            </button>
                        </div>
                    </div>
                ) : (
                    <div>
                        <div className="flex items-start justify-between">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Preferências de cookies
                            </h3>
                            <button
                                type="button"
                                onClick={close}
                                aria-label="Fechar"
                                className="text-gray-500 hover:text-gray-700"
                            >
                                ✕
                            </button>
                        </div>
                        <div className="mt-4 space-y-4">
                            {COOKIE_CATEGORIES.map((category) => {
                                const checked =
                                    category.id === "essential"
                                        ? true
                                        : category.id === "functional"
                                          ? functional
                                          : analytics;
                                const onChange = (value: boolean) => {
                                    const setters: Record<string, (v: boolean) => void> = {
                                        functional: setFunctional,
                                        analytics: setAnalytics,
                                    };
                                    setters[category.id]?.(value);
                                };
                                return (
                                    <label
                                        key={category.id}
                                        aria-label={category.label}
                                        className="flex items-start gap-3"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={checked}
                                            disabled={category.required}
                                            onChange={(e) =>
                                                onChange(e.target.checked)
                                            }
                                            className="mt-1"
                                        />
                                        <span>
                                            <span className="block text-sm font-medium text-gray-900">
                                                {category.label}
                                                {category.required && (
                                                    <span className="ml-2 text-xs text-gray-500">
                                                        (sempre ativo)
                                                    </span>
                                                )}
                                            </span>
                                            <span className="block text-sm text-gray-600">
                                                {category.description}
                                            </span>
                                        </span>
                                    </label>
                                );
                            })}
                        </div>
                        <div className="mt-6 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setMode("summary")}
                                className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Voltar
                            </button>
                            <button
                                type="button"
                                onClick={handleCustom}
                                disabled={saving}
                                className={cn(
                                    "rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700",
                                    /* v8 ignore next — cosmetic saving state styling */
                                    saving && "opacity-60",
                                )}
                            >
                                Salvar preferências
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
