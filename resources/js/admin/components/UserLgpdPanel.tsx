import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { adminLgpdApi, type AdminLgpdPayload } from "../api/adminClient";
import { cn } from "@shared/lib/utils";
import { Label } from "./ui/label";

interface Props {
    userId: number;
}

const EXPORT_STATUS_STYLES: Record<string, string> = {
    queued: "bg-gray-100 text-gray-700 ring-gray-200",
    running: "bg-blue-50 text-blue-700 ring-blue-200",
    completed: "bg-green-50 text-green-700 ring-green-200",
    failed: "bg-red-50 text-red-700 ring-red-200",
};

const EXPORT_STATUS_LABELS: Record<string, string> = {
    queued: "na fila",
    running: "em execução",
    completed: "concluída",
    failed: "falhou",
};

function ExportStatusBadge({ status }: { status: string }) {
    const cls = EXPORT_STATUS_STYLES[status] ?? EXPORT_STATUS_STYLES.queued;
    const label = EXPORT_STATUS_LABELS[status] ?? status;
    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset ${cls}`}
        >
            {label}
        </span>
    );
}

export function UserLgpdPanel({ userId }: Props) {
    const queryClient = useQueryClient();
    const [reasonOpen, setReasonOpen] = useState(false);
    const [reason, setReason] = useState("");
    const [formError, setFormError] = useState<string | null>(null);

    const query = useQuery({
        queryKey: ["admin-lgpd", userId],
        queryFn: async () => {
            const response = await adminLgpdApi.show(userId);
            return response.data as AdminLgpdPayload;
        },
    });

    const exportMutation = useMutation({
        mutationFn: () => adminLgpdApi.export(userId),
        onSuccess: () =>
            queryClient.invalidateQueries({
                queryKey: ["admin-lgpd", userId],
            }),
    });

    const anonymizeMutation = useMutation({
        mutationFn: (r: string) => adminLgpdApi.anonymize(userId, r),
        onSuccess: () => {
            setReasonOpen(false);
            setReason("");
            setFormError(null);
            queryClient.invalidateQueries({ queryKey: ["admin-lgpd", userId] });
        },
        onError: (err) => setFormError((err as Error).message),
    });

    if (query.isLoading) {
        return <p className="text-sm text-gray-500">Carregando…</p>;
    }

    if (query.isError) {
        return (
            <p className="text-sm text-red-600">Erro ao carregar dados LGPD.</p>
        );
    }

    const data = query.data!;
    const pending =
        data.anonymization_requested_at !== null && data.anonymized_at === null;
    const anonymized = data.anonymized_at !== null;

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap gap-2">
                <button
                    type="button"
                    onClick={() => exportMutation.mutate()}
                    disabled={exportMutation.isPending || anonymized}
                    className={cn(
                        "rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50",
                        (exportMutation.isPending || anonymized) && "opacity-60",
                    )}
                >
                    {exportMutation.isPending
                        ? "Enfileirando..."
                        : "Gerar exportação"}
                </button>
                <button
                    type="button"
                    onClick={() => setReasonOpen(true)}
                    disabled={anonymized}
                    className={cn(
                        "rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700",
                        anonymized && "opacity-60 cursor-not-allowed",
                    )}
                >
                    Anonimizar agora
                </button>
            </div>

            {pending && (
                <p className="text-sm text-amber-700">
                    Exclusão pendente desde{" "}
                    {data.anonymization_requested_at?.slice(0, 10)}.
                </p>
            )}
            {anonymized && (
                <p className="text-sm text-gray-600">
                    Conta anonimizada em {data.anonymized_at?.slice(0, 10)}.
                </p>
            )}

            <section>
                <div className="flex items-baseline justify-between">
                    <h3 className="text-sm font-semibold text-gray-900">
                        Consentimentos
                    </h3>
                    <span className="text-xs text-gray-500">
                        {data.consents.length}{" "}
                        {data.consents.length === 1 ? "registro" : "registros"}
                    </span>
                </div>
                {data.consents.length === 0 ? (
                    <p className="mt-3 rounded-md border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                        Nenhum registro.
                    </p>
                ) : (
                    <div className="mt-3 overflow-hidden rounded-lg border border-gray-200">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    <th className="px-3 py-2">Tipo</th>
                                    <th className="px-3 py-2">Estado</th>
                                    <th className="px-3 py-2">Versão</th>
                                    <th className="px-3 py-2">Origem</th>
                                    <th className="px-3 py-2">Quando</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {data.consents.map((c, i) => (
                                    <tr
                                        key={i}
                                        className="hover:bg-gray-50"
                                    >
                                        <td className="px-3 py-2 font-mono text-xs text-gray-700">
                                            {c.type}
                                        </td>
                                        <td className="px-3 py-2">
                                            <span
                                                className={
                                                    c.granted
                                                        ? "inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-200"
                                                        : "inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200"
                                                }
                                            >
                                                <span
                                                    className={
                                                        c.granted
                                                            ? "h-1.5 w-1.5 rounded-full bg-green-500"
                                                            : "h-1.5 w-1.5 rounded-full bg-gray-400"
                                                    }
                                                />
                                                {c.granted
                                                    ? "concedido"
                                                    : "revogado"}
                                            </span>
                                        </td>
                                        <td className="px-3 py-2 text-gray-600">
                                            {c.version ?? "—"}
                                        </td>
                                        <td className="px-3 py-2 text-gray-600">
                                            {c.source ?? "—"}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-500">
                                            {c.created_at?.slice(0, 10) ?? "—"}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>

            <section>
                <div className="flex items-baseline justify-between">
                    <h3 className="text-sm font-semibold text-gray-900">
                        Exportações de dados
                    </h3>
                    <span className="text-xs text-gray-500">
                        {data.data_export_requests.length}{" "}
                        {data.data_export_requests.length === 1
                            ? "registro"
                            : "registros"}
                    </span>
                </div>
                {data.data_export_requests.length === 0 ? (
                    <p className="mt-3 rounded-md border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                        Nenhuma exportação solicitada.
                    </p>
                ) : (
                    <div className="mt-3 overflow-hidden rounded-lg border border-gray-200">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    <th className="px-3 py-2">ID</th>
                                    <th className="px-3 py-2">Status</th>
                                    <th className="px-3 py-2">Criado</th>
                                    <th className="px-3 py-2">Concluído</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {data.data_export_requests.map((r) => (
                                    <tr
                                        key={r.id}
                                        className="hover:bg-gray-50"
                                    >
                                        <td className="px-3 py-2 font-mono text-xs text-gray-500">
                                            #{r.id}
                                        </td>
                                        <td className="px-3 py-2">
                                            <ExportStatusBadge
                                                status={r.status}
                                            />
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-500">
                                            {r.created_at?.slice(0, 10) ?? "—"}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-500">
                                            {r.completed_at?.slice(0, 10) ?? "—"}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>

            {reasonOpen && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="anonymize-dialog-title"
                >
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                        <h3
                            id="anonymize-dialog-title"
                            className="text-lg font-semibold text-gray-900"
                        >
                            Anonimizar conta
                        </h3>
                        <p className="mt-2 text-sm text-gray-600">
                            Esta ação é irreversível. Informe o motivo da
                            solicitação (ticket, e-mail do titular, órgão) para
                            registro no log de auditoria.
                        </p>
                        <div className="mt-4">
                            <Label htmlFor="anonymize-reason">Motivo</Label>
                            <textarea
                                id="anonymize-reason"
                                value={reason}
                                onChange={(e) => setReason(e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                rows={3}
                            />
                        </div>
                        {formError && (
                            <p className="mt-2 text-sm text-red-600">
                                {formError}
                            </p>
                        )}
                        <div className="mt-6 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => {
                                    setReasonOpen(false);
                                    setReason("");
                                    setFormError(null);
                                }}
                                className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                onClick={() =>
                                    anonymizeMutation.mutate(reason)
                                }
                                disabled={
                                    reason.length < 5 ||
                                    anonymizeMutation.isPending
                                }
                                className={cn(
                                    "rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700",
                                    (reason.length < 5 ||
                                        anonymizeMutation.isPending) &&
                                        "opacity-60",
                                )}
                            >
                                Confirmar anonimização
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
