import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { dataPrivacyApi } from "@shared/api/client";
import { cn } from "@shared/lib/utils";
import { getErrorMessage } from "@shared/lib/errors";

type UserWithLgpd = {
    id: number;
    name: string;
    email: string;
    anonymization_requested_at?: string | null;
};

interface Props {
    user: UserWithLgpd;
    onUpdate: () => void;
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

export function PrivacySection({ user, onUpdate }: Props) {
    const queryClient = useQueryClient();
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [password, setPassword] = useState("");
    const [formError, setFormError] = useState<string | null>(null);
    const [confirmationSent, setConfirmationSent] = useState(false);

    const exportsQuery = useQuery({
        queryKey: ["data-privacy", "exports"],
        queryFn: () => dataPrivacyApi.listExports(),
    });

    const requestExport = useMutation({
        mutationFn: () => dataPrivacyApi.requestExport(),
        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: ["data-privacy", "exports"],
            });
        },
    });

    const requestDeletion = useMutation({
        mutationFn: (pw: string) => dataPrivacyApi.requestDeletion(pw),
        onSuccess: () => {
            setDeleteModalOpen(false);
            setPassword("");
            setFormError(null);
            setConfirmationSent(true);
        },
        onError: (err) => setFormError(getErrorMessage(err)),
    });

    const cancelDeletion = useMutation({
        mutationFn: () => dataPrivacyApi.cancelDeletion(),
        onSuccess: () => onUpdate(),
    });

    const pending = !!user.anonymization_requested_at;

    return (
        <section className="rounded-lg border border-gray-200 bg-white p-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-lg font-semibold text-gray-900">
                        Privacidade e dados (LGPD)
                    </h2>
                    <p className="text-sm text-gray-500">
                        Exporte seus dados ou exclua sua conta a qualquer
                        momento.
                    </p>
                </div>
            </div>

            <div className="mt-6 grid gap-4 md:grid-cols-2">
                <div className="rounded border border-gray-200 p-4">
                    <h3 className="font-medium text-gray-900">
                        Exportar meus dados
                    </h3>
                    <p className="mt-1 text-sm text-gray-600">
                        Receba um arquivo <code>.zip</code> com todos os seus
                        dados em formato JSON. Enviaremos um link por e-mail
                        (válido por 1 dia).
                    </p>
                    <button
                        type="button"
                        onClick={() => requestExport.mutate()}
                        disabled={requestExport.isPending}
                        className={cn(
                            "mt-3 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50",
                            /* v8 ignore next */
                            requestExport.isPending && "opacity-60",
                        )}
                    >
                        {/* v8 ignore start -- @preserve cosmetic pending-state label */}
                        {requestExport.isPending
                            ? "Enviando..."
                            : "Exportar meus dados"}
                        {/* v8 ignore stop */}
                    </button>
                    {requestExport.isError && (
                        <p className="mt-2 text-sm text-red-600">
                            {getErrorMessage(requestExport.error)}
                        </p>
                    )}
                    {requestExport.isSuccess && (
                        <p className="mt-2 text-sm text-green-600">
                            Solicitação recebida. Você receberá um e-mail em
                            breve.
                        </p>
                    )}
                    {exportsQuery.data?.data &&
                        exportsQuery.data.data.length > 0 && (
                            <div className="mt-4 overflow-hidden rounded-lg border border-gray-200">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                            <th className="px-3 py-2">Data</th>
                                            <th className="px-3 py-2">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {exportsQuery.data.data
                                            .slice(0, 3)
                                            .map((r) => (
                                                <tr
                                                    key={r.id}
                                                    className="hover:bg-gray-50"
                                                >
                                                    <td className="px-3 py-2 whitespace-nowrap text-gray-500">
                                                        {r.created_at?.slice(
                                                            0,
                                                            10,
                                                        ) ?? "—"}
                                                    </td>
                                                    <td className="px-3 py-2">
                                                        <ExportStatusBadge
                                                            status={r.status}
                                                        />
                                                    </td>
                                                </tr>
                                            ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                </div>

                <div className="rounded border border-red-200 bg-red-50/40 p-4">
                    <h3 className="font-medium text-red-900">
                        Excluir minha conta
                    </h3>
                    <p className="mt-1 text-sm text-gray-700">
                        {pending
                            ? `Sua conta será excluída em 30 dias a partir de ${user.anonymization_requested_at?.slice(0, 10)}. Faça login para cancelar, ou use o botão abaixo.`
                            : "Dados pessoais serão pseudonimizados após 30 dias. Registros acadêmicos de presença são preservados como documentação institucional."}
                    </p>
                    {confirmationSent && !pending && (
                        <p className="mt-3 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                            Enviamos um link de confirmação para{" "}
                            <strong>{user.email}</strong>. Clique nele em até 1
                            hora para finalizar a exclusão.
                        </p>
                    )}
                    {pending ? (
                        <button
                            type="button"
                            onClick={() => cancelDeletion.mutate()}
                            disabled={cancelDeletion.isPending}
                            className={cn(
                                "mt-3 rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50",
                                /* v8 ignore next */
                                cancelDeletion.isPending && "opacity-60",
                            )}
                        >
                            Cancelar exclusão
                        </button>
                    ) : !confirmationSent ? (
                        <button
                            type="button"
                            onClick={() => setDeleteModalOpen(true)}
                            className="mt-3 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                        >
                            Excluir minha conta
                        </button>
                    ) : null}
                </div>
            </div>

            {deleteModalOpen && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                    role="dialog"
                    aria-modal="true"
                >
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                        <h3 className="text-lg font-semibold text-gray-900">
                            Confirme a exclusão da conta
                        </h3>
                        <p className="mt-2 text-sm text-gray-600">
                            Digite sua senha para solicitar a exclusão. Você
                            terá 30 dias para cancelar antes que os dados sejam
                            pseudonimizados.
                        </p>
                        <label className="mt-4 block text-sm font-medium text-gray-700">
                            Senha
                            <input
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                            />
                        </label>
                        {formError && (
                            <p className="mt-2 text-sm text-red-600">
                                {formError}
                            </p>
                        )}
                        <div className="mt-6 flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => {
                                    setDeleteModalOpen(false);
                                    setPassword("");
                                    setFormError(null);
                                }}
                                className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                onClick={() =>
                                    requestDeletion.mutate(password)
                                }
                                disabled={
                                    !password || requestDeletion.isPending
                                }
                                className={cn(
                                    "rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700",
                                    (!password || requestDeletion.isPending) &&
                                        "opacity-60",
                                )}
                            >
                                Confirmar exclusão
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </section>
    );
}
