import { useEffect, useState } from "react";
import { PageTitle } from "@shared/components/PageTitle";
import { useConsent } from "@shared/contexts/ConsentContext";
import { COOKIE_CATEGORIES } from "@shared/lib/consent";
import { Layout } from "../components/Layout";
import { cn } from "@shared/lib/utils";

export default function CookiePreferences() {
    const { state, save } = useConsent();
    const [functional, setFunctional] = useState(state?.functional ?? false);
    const [analytics, setAnalytics] = useState(state?.analytics ?? false);
    const [saving, setSaving] = useState(false);
    const [savedAt, setSavedAt] = useState<string | null>(null);

    useEffect(() => {
        if (state) {
            setFunctional(state.functional);
            setAnalytics(state.analytics);
        }
    }, [state]);

    const onSave = async () => {
        setSaving(true);
        try {
            await save({ functional, analytics });
            setSavedAt(new Date().toLocaleString("pt-BR"));
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            <PageTitle title="Preferências de Cookies" />
            <Layout>
                <div className="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
                    <h1 className="text-2xl font-bold text-gray-900">
                        Preferências de Cookies
                    </h1>
                    <p className="mt-2 text-gray-600">
                        Ajuste quais categorias de cookies podem ser utilizadas
                        durante sua navegação. Alterações valem apenas para
                        este dispositivo.
                    </p>

                    <div className="mt-8 space-y-4">
                        {COOKIE_CATEGORIES.map((category) => {
                            const checked =
                                category.id === "essential"
                                    ? true
                                    : category.id === "functional"
                                      ? functional
                                      : analytics;
                            const onChange = (value: boolean) => {
                                if (category.id === "functional")
                                    setFunctional(value);
                                else if (category.id === "analytics")
                                    setAnalytics(value);
                            };
                            return (
                                <label
                                    key={category.id}
                                    className="flex items-start gap-3 rounded-md border border-gray-200 p-4"
                                >
                                    <input
                                        type="checkbox"
                                        checked={checked}
                                        disabled={category.required}
                                        onChange={(e) => onChange(e.target.checked)}
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

                    <div className="mt-6 flex items-center gap-4">
                        <button
                            type="button"
                            onClick={onSave}
                            disabled={saving}
                            className={cn(
                                "rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700",
                                saving && "opacity-60",
                            )}
                        >
                            Salvar preferências
                        </button>
                        {savedAt && (
                            <span className="text-sm text-gray-500">
                                Salvo em {savedAt}.
                            </span>
                        )}
                    </div>
                </div>
            </Layout>
        </>
    );
}
