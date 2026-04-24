import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { adminLgpdApi, type AdminLgpdPayload } from "../api/adminClient";
import { cn } from "@shared/lib/utils";
import { Label } from "./ui/label";

interface Props {
    userId: number;
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
                <h3 className="text-sm font-semibold text-gray-900">
                    Consentimentos
                </h3>
                {data.consents.length === 0 ? (
                    <p className="mt-2 text-sm text-gray-500">
                        Nenhum registro.
                    </p>
                ) : (
                    <table className="mt-2 w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500">
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Versão</th>
                                <th>Origem</th>
                                <th>Quando</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.consents.map((c, i) => (
                                <tr key={i} className="border-t">
                                    <td>{c.type}</td>
                                    <td>
                                        {c.granted ? "concedido" : "revogado"}
                                    </td>
                                    <td>{c.version ?? "—"}</td>
                                    <td>{c.source ?? "—"}</td>
                                    <td>{c.created_at?.slice(0, 10) ?? "—"}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </section>

            <section>
                <h3 className="text-sm font-semibold text-gray-900">
                    Exportações de dados
                </h3>
                {data.data_export_requests.length === 0 ? (
                    <p className="mt-2 text-sm text-gray-500">Nenhuma.</p>
                ) : (
                    <ul className="mt-2 space-y-1 text-sm">
                        {data.data_export_requests.map((r) => (
                            <li key={r.id}>
                                #{r.id} — {r.status} —{" "}
                                {r.created_at?.slice(0, 10)}
                            </li>
                        ))}
                    </ul>
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
