import { twoFactorApi } from "@shared/api/twoFactorApi";
import { FormField } from "@shared/components/FormField";
import { useAuth } from "@shared/contexts/AuthContext";
import { cn, formatDateTime } from "@shared/lib/utils";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { ShieldCheck } from "lucide-react";
import { useState } from "react";
import { ErrorAlert, SuccessAlert } from "./FormAlerts";

type SetupState = {
    secret: string;
    qrCodeSvg: string;
    recoveryCodes: string[];
};

export function TwoFactorSection() {
    const { user, refreshUser } = useAuth();
    const qc = useQueryClient();

    const [setup, setSetup] = useState<SetupState | null>(null);
    const [code, setCode] = useState("");
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const enable = useMutation({
        mutationFn: () => twoFactorApi.enable(),
        onSuccess: (r) => {
            setError(null);
            setSetup({
                secret: r.secret,
                qrCodeSvg: r.qr_code_svg,
                recoveryCodes: r.recovery_codes,
            });
        },
        onError: (e: Error) => setError(e.message),
    });

    const confirm = useMutation({
        mutationFn: (c: string) => twoFactorApi.confirm(c),
        onSuccess: async () => {
            setSetup(null);
            setCode("");
            setError(null);
            setSuccess("Autenticação em duas etapas ativada com sucesso.");
            await refreshUser();
        },
        onError: (e: Error) => setError(e.message),
    });

    const disable = useMutation({
        mutationFn: () => twoFactorApi.disable(),
        onSuccess: async () => {
            setSetup(null);
            setError(null);
            setSuccess("Autenticação em duas etapas desativada.");
            await refreshUser();
            qc.invalidateQueries({ queryKey: ["profile", "two-factor-devices"] });
        },
        onError: (e: Error) => setError(e.message),
    });

    const regen = useMutation({
        mutationFn: () => twoFactorApi.regenerateRecoveryCodes(),
        onSuccess: (r) => {
            setError(null);
            setSetup({
                secret: "",
                qrCodeSvg: "",
                recoveryCodes: r.recovery_codes,
            });
        },
        onError: (e: Error) => setError(e.message),
    });

    const devicesQuery = useQuery({
        queryKey: ["profile", "two-factor-devices"],
        queryFn: () => twoFactorApi.listDevices(),
        enabled: Boolean(user?.two_factor_enabled),
    });

    const revoke = useMutation({
        mutationFn: (id: number) => twoFactorApi.revokeDevice(id),
        onSuccess: () =>
            qc.invalidateQueries({ queryKey: ["profile", "two-factor-devices"] }),
    });

    const isEnabled = Boolean(user?.two_factor_enabled);

    return (
        <section className="rounded-lg border border-gray-200 bg-white">
            <div className="border-b border-gray-200 px-6 py-4">
                <div className="flex items-center gap-2">
                    <ShieldCheck className="h-5 w-5 text-gray-400" />
                    <h2 className="text-lg font-semibold text-gray-900">
                        Autenticação em duas etapas
                    </h2>
                </div>
                <p className="mt-1 text-sm text-gray-500">
                    Aumente a segurança da sua conta exigindo um código do seu app autenticador a cada login.
                </p>
            </div>

            <div className="space-y-4 px-6 py-4">
                {success && <SuccessAlert message={success} />}
                {error && <ErrorAlert message={error} />}

                {!isEnabled && !setup && (
                    <button
                        type="button"
                        onClick={() => {
                            setSuccess(null);
                            enable.mutate();
                        }}
                        disabled={enable.isPending}
                        className={cn(
                            "rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer",
                            enable.isPending && "opacity-70 cursor-not-allowed",
                        )}
                    >
                        {enable.isPending ? "Gerando..." : "Ativar"}
                    </button>
                )}

                {setup && (
                    <div className="space-y-4">
                        {setup.qrCodeSvg && (
                            <>
                                <p className="text-sm text-gray-700">
                                    Escaneie o QR code abaixo com seu app autenticador (Google Authenticator, Authy, 1Password, etc):
                                </p>
                                <div
                                    aria-label="QR Code"
                                    className="inline-block w-48 rounded border border-gray-200 bg-white p-2"
                                    dangerouslySetInnerHTML={{ __html: setup.qrCodeSvg }}
                                />
                                {setup.secret && (
                                    <p className="break-all text-xs text-gray-600">
                                        Chave manual: <code className="font-mono">{setup.secret}</code>
                                    </p>
                                )}
                            </>
                        )}

                        <div className="rounded-md bg-yellow-50 p-3">
                            <p className="text-sm font-medium text-yellow-900">
                                Códigos de recuperação (guarde em local seguro):
                            </p>
                            <ul className="mt-2 grid grid-cols-2 gap-1 font-mono text-sm text-yellow-900">
                                {setup.recoveryCodes.map((c) => (
                                    <li key={c}>{c}</li>
                                ))}
                            </ul>
                        </div>

                        {setup.qrCodeSvg && (
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    confirm.mutate(code);
                                }}
                                className="space-y-3"
                            >
                                <FormField
                                    id="two-factor-confirm"
                                    name="code"
                                    label="Código do app"
                                    type="text"
                                    inputMode="numeric"
                                    pattern="[0-9]{6}"
                                    maxLength={6}
                                    required
                                    autoComplete="one-time-code"
                                    value={code}
                                    onChange={(e) =>
                                        setCode(e.target.value.replace(/\D/g, ""))
                                    }
                                />
                                <button
                                    type="submit"
                                    disabled={confirm.isPending || code.length !== 6}
                                    className={cn(
                                        "rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer",
                                        (confirm.isPending || code.length !== 6) &&
                                            "opacity-70 cursor-not-allowed",
                                    )}
                                >
                                    {confirm.isPending ? "Confirmando..." : "Confirmar"}
                                </button>
                            </form>
                        )}

                        {!setup.qrCodeSvg && (
                            <button
                                type="button"
                                onClick={() => setSetup(null)}
                                className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer"
                            >
                                Fechar
                            </button>
                        )}
                    </div>
                )}

                {isEnabled && !setup && (
                    <div className="space-y-4">
                        <div className="flex flex-wrap gap-2">
                            <button
                                type="button"
                                onClick={() => {
                                    setSuccess(null);
                                    regen.mutate();
                                }}
                                disabled={regen.isPending}
                                className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer disabled:opacity-70"
                            >
                                Gerar novos códigos de recuperação
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    setSuccess(null);
                                    disable.mutate();
                                }}
                                disabled={disable.isPending}
                                className="rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 cursor-pointer disabled:opacity-70"
                            >
                                Desativar
                            </button>
                        </div>

                        <div>
                            <h3 className="mb-2 text-sm font-semibold text-gray-700">
                                Dispositivos lembrados
                            </h3>
                            {devicesQuery.data?.devices.length ? (
                                <ul className="divide-y divide-gray-200 rounded-md border border-gray-200">
                                    {devicesQuery.data.devices.map((d) => (
                                        <li
                                            key={d.id}
                                            className="flex items-center justify-between gap-3 px-3 py-2 text-sm"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate font-medium text-gray-900">
                                                    {d.label ?? "Dispositivo"}
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    {d.ip ?? "?"} ·{" "}
                                                    {d.last_used_at
                                                        ? `Último uso: ${formatDateTime(d.last_used_at)}`
                                                        : "Nunca usado"}
                                                </p>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => revoke.mutate(d.id)}
                                                disabled={revoke.isPending}
                                                className="text-sm font-medium text-red-600 hover:text-red-700 cursor-pointer disabled:opacity-70"
                                            >
                                                Revogar
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-gray-500">
                                    Nenhum dispositivo lembrado.
                                </p>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}
