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
        queryKey: ["subjects-top-30"],
        queryFn: () => subjectsApi.list({ sort: "seminars", limit: 30 }),
    });

    const [newSeminarAlert, setNewSeminarAlert] = useState(false);
    const [seminarTypeIds, setSeminarTypeIds] = useState<number[]>([]);
    const [subjectIds, setSubjectIds] = useState<number[]>([]);
    const [seminarReminder7d, setSeminarReminder7d] = useState(true);
    const [seminarReminder24h, setSeminarReminder24h] = useState(true);
    const [evaluationPrompt, setEvaluationPrompt] = useState(true);
    const [announcements, setAnnouncements] = useState(true);
    const [workshopAnnouncements, setWorkshopAnnouncements] = useState(true);
    const [certificateReady, setCertificateReady] = useState(true);
    const [seminarRescheduled, setSeminarRescheduled] = useState(true);
    const [success, setSuccess] = useState(false);

    useEffect(() => {
        if (preference) {
            setNewSeminarAlert(preference.newSeminarAlert);
            setSeminarTypeIds(preference.seminarTypeIds);
            setSubjectIds(preference.subjectIds);
            setSeminarReminder7d(preference.seminarReminder7d);
            setSeminarReminder24h(preference.seminarReminder24h);
            setEvaluationPrompt(preference.evaluationPrompt);
            setAnnouncements(preference.announcements);
            setWorkshopAnnouncements(preference.workshopAnnouncements);
            setCertificateReady(preference.certificateReady);
            setSeminarRescheduled(preference.seminarRescheduled);
        }
    }, [preference]);

    const mutation = useMutation({
        mutationFn: () =>
            alertPreferencesApi.update({
                new_seminar_alert: newSeminarAlert,
                seminar_type_ids: seminarTypeIds,
                subject_ids: subjectIds,
                seminar_reminder_7d: seminarReminder7d,
                seminar_reminder_24h: seminarReminder24h,
                evaluation_prompt: evaluationPrompt,
                announcements,
                workshop_announcements: workshopAnnouncements,
                certificate_ready: certificateReady,
                seminar_rescheduled: seminarRescheduled,
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
            <PageTitle title="Preferências de comunicação" />
            <Layout>
                <div className="bg-white border-b border-gray-200">
                    <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                        <h1 className="text-2xl font-bold text-gray-900">
                            Preferências de comunicação
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Escolha quais e-mails você deseja receber do sistema.
                        </p>
                    </div>
                </div>

                <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8 space-y-6">
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

                            <section className="rounded-lg border border-gray-200 bg-white">
                                <div className="border-b border-gray-200 px-6 py-4">
                                    <div className="flex items-center gap-2">
                                        <Bell className="h-5 w-5 text-gray-400" />
                                        <h2 className="text-lg font-semibold text-gray-900">
                                            E-mails transacionais
                                        </h2>
                                    </div>
                                    <p className="mt-1 text-xs text-gray-500">
                                        Esses e-mails estão ativados por padrão. Desmarque o que você não quer mais receber.
                                    </p>
                                </div>
                                <div className="px-6 py-6 space-y-4">
                                    {([
                                        { id: "pref-sr24h", label: "Lembrete 24h antes do seminário", checked: seminarReminder24h, set: setSeminarReminder24h },
                                        { id: "pref-sr7d", label: "Lembrete 7 dias antes do seminário", checked: seminarReminder7d, set: setSeminarReminder7d },
                                        { id: "pref-eval", label: "Pedido para avaliar seminários assistidos", checked: evaluationPrompt, set: setEvaluationPrompt },
                                        { id: "pref-cert", label: "Certificado pronto", checked: certificateReady, set: setCertificateReady },
                                        { id: "pref-resched", label: "Seminário reagendado", checked: seminarRescheduled, set: setSeminarRescheduled },
                                        { id: "pref-announce", label: "Comunicados gerais", checked: announcements, set: setAnnouncements },
                                        { id: "pref-workshop-ann", label: "Comunicado de novos workshops", checked: workshopAnnouncements, set: setWorkshopAnnouncements },
                                    ] as const).map((row) => (
                                        <label key={row.id} className="flex items-start gap-3 cursor-pointer select-none">
                                            <input
                                                id={row.id}
                                                type="checkbox"
                                                checked={row.checked}
                                                onChange={(e) => row.set(e.target.checked)}
                                                className="mt-1 h-4 w-4 rounded border-gray-300 cursor-pointer"
                                            />
                                            <span className="text-sm text-gray-900">{row.label}</span>
                                        </label>
                                    ))}
                                </div>
                            </section>

                            <section className="rounded-lg border border-gray-200 bg-white">
                                <div className="border-b border-gray-200 px-6 py-4">
                                    <div className="flex items-center gap-2">
                                        <Bell className="h-5 w-5 text-gray-400" />
                                        <h2 className="text-lg font-semibold text-gray-900">
                                            Alertas de novos seminários
                                        </h2>
                                    </div>
                                    <p className="mt-1 text-xs text-gray-500">
                                        Receba um e-mail quando um novo seminário ativo for publicado. Deixe os filtros vazios para receber alertas de todos os seminários.
                                    </p>
                                </div>

                                <div className="px-6 py-6 space-y-6">
                                    <div className="flex items-start gap-3">
                                        <input
                                            id="alert-preferences-opted-in"
                                            type="checkbox"
                                            checked={newSeminarAlert}
                                            onChange={(e) => setNewSeminarAlert(e.target.checked)}
                                            className="mt-1 h-4 w-4 rounded border-gray-300 cursor-pointer"
                                        />
                                        <label
                                            htmlFor="alert-preferences-opted-in"
                                            className="cursor-pointer select-none"
                                        >
                                            <span className="block text-sm font-medium text-gray-900">
                                                Quero receber alertas por e-mail
                                            </span>
                                            <span className="block text-xs text-gray-500 mt-0.5">
                                                Você pode desativar a qualquer momento.
                                            </span>
                                        </label>
                                    </div>

                                    <fieldset
                                        disabled={!newSeminarAlert}
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
                                                                "flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm cursor-pointer select-none transition-colors max-w-full min-w-0 break-all",
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
                                                Vazio = todos os assuntos. Mostrando os 30 com mais apresentações.
                                            </p>
                                            <div className="flex flex-wrap gap-2">
                                                {subjects.map((subject) => {
                                                    const checked = subjectIds.includes(subject.id);
                                                    return (
                                                        <label
                                                            key={subject.id}
                                                            className={cn(
                                                                "flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm cursor-pointer select-none transition-colors max-w-full min-w-0 break-all",
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

                                </div>
                            </section>

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
            </Layout>
        </ProtectedRoute>
    );
}
