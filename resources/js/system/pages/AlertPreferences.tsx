import {
    alertPreferencesApi,
    seminarTypesApi,
    subjectsApi,
} from "@shared/api/client";
import { PageTitle } from "@shared/components/PageTitle";
import { cn } from "@shared/lib/utils";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Bell } from "lucide-react";
import { useEffect, useState } from "react";
import { Layout } from "../components/Layout";
import { ProtectedRoute } from "../components/ProtectedRoute";
import { ErrorAlert, SuccessAlert } from "../components/profile/FormAlerts";

export default function AlertPreferences() {
    const queryClient = useQueryClient();

    const { data: preference, isLoading } = useQuery({
        queryKey: ["alert-preferences"],
        queryFn: alertPreferencesApi.get,
    });

    const { data: seminarTypes } = useQuery({
        queryKey: ["seminar-types"],
        queryFn: () => seminarTypesApi.list(),
    });

    const { data: subjectsResponse } = useQuery({
        queryKey: ["subjects-all"],
        queryFn: () => subjectsApi.list(),
    });

    const [optedIn, setOptedIn] = useState(false);
    const [seminarTypeIds, setSeminarTypeIds] = useState<number[]>([]);
    const [subjectIds, setSubjectIds] = useState<number[]>([]);
    const [success, setSuccess] = useState(false);

    useEffect(() => {
        if (preference) {
            setOptedIn(preference.optedIn);
            setSeminarTypeIds(preference.seminarTypeIds);
            setSubjectIds(preference.subjectIds);
        }
    }, [preference]);

    const mutation = useMutation({
        mutationFn: () =>
            alertPreferencesApi.update({
                opted_in: optedIn,
                seminar_type_ids: seminarTypeIds,
                subject_ids: subjectIds,
            }),
        onSuccess: (updated) => {
            queryClient.setQueryData(["alert-preferences"], updated);
            setSuccess(true);
            setTimeout(() => setSuccess(false), 3000);
        },
    });

    const toggleId = (id: number, list: number[]): number[] => {
        return list.includes(id) ? list.filter((x) => x !== id) : [...list, id];
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate();
    };

    const types = seminarTypes?.data ?? [];
    const subjects = subjectsResponse?.data ?? [];

    return (
        <ProtectedRoute>
            <PageTitle title="Preferências de Alerta" />
            <Layout>
                <div className="bg-white border-b border-gray-200">
                    <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                        <h1 className="text-2xl font-bold text-gray-900">
                            Alertas de novos seminários
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Receba um e-mail quando um novo seminário ativo for publicado.
                            Deixe os filtros vazios para receber alertas de todos os seminários.
                        </p>
                    </div>
                </div>

                <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                    <section className="rounded-lg border border-gray-200 bg-white">
                        <div className="border-b border-gray-200 px-6 py-4">
                            <div className="flex items-center gap-2">
                                <Bell className="h-5 w-5 text-gray-400" />
                                <h2 className="text-lg font-semibold text-gray-900">
                                    Suas preferências
                                </h2>
                            </div>
                        </div>

                        <div className="px-6 py-6">
                            {isLoading ? (
                                <p className="text-sm text-gray-500">Carregando…</p>
                            ) : (
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    {success && (
                                        <SuccessAlert message="Preferências salvas com sucesso!" />
                                    )}

                                    {mutation.isError && (
                                        <ErrorAlert message="Não foi possível salvar suas preferências. Tente novamente." />
                                    )}

                                    <label className="flex items-start gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={optedIn}
                                            onChange={(e) => setOptedIn(e.target.checked)}
                                            className="mt-1 h-4 w-4 rounded border-gray-300"
                                        />
                                        <span>
                                            <span className="block text-sm font-medium text-gray-900">
                                                Quero receber alertas por e-mail
                                            </span>
                                            <span className="block text-xs text-gray-500 mt-0.5">
                                                Você pode desativar a qualquer momento.
                                            </span>
                                        </span>
                                    </label>

                                    <fieldset
                                        disabled={!optedIn}
                                        className="space-y-6 disabled:opacity-50"
                                    >
                                        <div>
                                            <legend className="block text-sm font-medium text-gray-900 mb-2">
                                                Tipos de seminário (opcional)
                                            </legend>
                                            <p className="text-xs text-gray-500 mb-3">
                                                Vazio = todos os tipos.
                                            </p>
                                            <div className="flex flex-wrap gap-2">
                                                {types.map((type) => {
                                                    const checked = seminarTypeIds.includes(type.id);
                                                    return (
                                                        <label
                                                            key={type.id}
                                                            className={cn(
                                                                "flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm cursor-pointer transition-colors",
                                                                checked
                                                                    ? "border-primary-600 bg-primary-50 text-primary-900"
                                                                    : "border-gray-300 bg-white text-gray-700 hover:bg-gray-50",
                                                            )}
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                checked={checked}
                                                                onChange={() =>
                                                                    setSeminarTypeIds(
                                                                        toggleId(type.id, seminarTypeIds),
                                                                    )
                                                                }
                                                                className="h-4 w-4 rounded border-gray-300"
                                                            />
                                                            {type.name}
                                                        </label>
                                                    );
                                                })}
                                            </div>
                                        </div>

                                        <div>
                                            <legend className="block text-sm font-medium text-gray-900 mb-2">
                                                Assuntos (opcional)
                                            </legend>
                                            <p className="text-xs text-gray-500 mb-3">
                                                Vazio = todos os assuntos.
                                            </p>
                                            <div className="flex flex-wrap gap-2">
                                                {subjects.map((subject) => {
                                                    const checked = subjectIds.includes(subject.id);
                                                    return (
                                                        <label
                                                            key={subject.id}
                                                            className={cn(
                                                                "flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm cursor-pointer transition-colors",
                                                                checked
                                                                    ? "border-primary-600 bg-primary-50 text-primary-900"
                                                                    : "border-gray-300 bg-white text-gray-700 hover:bg-gray-50",
                                                            )}
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                checked={checked}
                                                                onChange={() =>
                                                                    setSubjectIds(toggleId(subject.id, subjectIds))
                                                                }
                                                                className="h-4 w-4 rounded border-gray-300"
                                                            />
                                                            {subject.name}
                                                        </label>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    </fieldset>

                                    <div className="flex justify-end pt-2">
                                        <button
                                            type="submit"
                                            disabled={mutation.isPending}
                                            className={cn(
                                                "rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer",
                                                mutation.isPending &&
                                                    "opacity-70 cursor-not-allowed",
                                            )}
                                        >
                                            {mutation.isPending ? "Salvando..." : "Salvar preferências"}
                                        </button>
                                    </div>
                                </form>
                            )}
                        </div>
                    </section>
                </div>
            </Layout>
        </ProtectedRoute>
    );
}
