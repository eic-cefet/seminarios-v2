import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useRef, useState } from "react";
import { Check, Copy, ExternalLink, Loader2, RefreshCw } from "lucide-react";
import { profileApi } from "@shared/api/client";
import { Button } from "@shared/components/Button";
import { googleCalendarSubscribeUrl, toWebcalUrl } from "@shared/lib/calendar";

function CopyField({ label, value }: { label: string; value: string }) {
    const [copied, setCopied] = useState(false);
    const timerRef = useRef<ReturnType<typeof setTimeout>>(undefined);

    useEffect(() => {
        return () => clearTimeout(timerRef.current);
    }, []);

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(value);
            setCopied(true);
            timerRef.current = setTimeout(() => setCopied(false), 2000);
        } catch {
            // Clipboard unavailable — the URL stays visible in the input for manual copy.
        }
    };

    return (
        <div>
            <label htmlFor={label} className="text-xs font-medium text-gray-500">
                {label}
            </label>
            <div className="mt-1 flex items-center gap-2">
                <input
                    id={label}
                    type="text"
                    readOnly
                    value={value}
                    onFocus={(event) => event.target.select()}
                    className="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700"
                />
                <Button
                    type="button"
                    variant="outline"
                    className="shrink-0 px-3 py-2"
                    onClick={copy}
                >
                    {copied ? (
                        <Check className="h-4 w-4" />
                    ) : (
                        <Copy className="h-4 w-4" />
                    )}
                    {copied ? "Copiado!" : "Copiar"}
                </Button>
            </div>
        </div>
    );
}

export function CalendarSubscribeCard() {
    const queryClient = useQueryClient();
    const [confirmingRotate, setConfirmingRotate] = useState(false);

    const { data, isLoading, isError } = useQuery({
        queryKey: ["profile", "calendar-feed"],
        queryFn: () => profileApi.calendarFeed(),
    });

    const rotateMutation = useMutation({
        mutationFn: () => profileApi.rotateCalendarFeed(),
        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: ["profile", "calendar-feed"],
            });
            setConfirmingRotate(false);
        },
    });

    if (isLoading) {
        return (
            <div className="flex items-center justify-center rounded-lg border border-gray-200 bg-white py-8">
                <Loader2 className="h-6 w-6 animate-spin text-gray-400" />
            </div>
        );
    }

    if (isError || !data) {
        return (
            <div className="rounded-lg border border-gray-200 bg-white px-6 py-4 text-sm text-gray-500">
                Não foi possível carregar os links de assinatura.
            </div>
        );
    }

    return (
        <div className="rounded-lg border border-gray-200 bg-white px-6 py-5 space-y-4">
            <div>
                <h2 className="text-lg font-semibold text-gray-900">
                    Assine sua agenda
                </h2>
                <p className="mt-1 text-sm text-gray-500">
                    Adicione seus seminários ao seu calendário e receba
                    atualizações automáticas, incluindo remarcações.
                </p>
            </div>

            <div className="flex flex-wrap gap-2">
                <Button asChild variant="primary" className="px-3 py-2">
                    <a
                        href={googleCalendarSubscribeUrl(data.data.personal_url)}
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <ExternalLink className="h-4 w-4" />
                        Adicionar ao Google Calendar
                    </a>
                </Button>
            </div>

            <CopyField
                label="Minha agenda (seminários inscritos)"
                value={toWebcalUrl(data.data.personal_url)}
            />
            <CopyField
                label="Todos os seminários"
                value={toWebcalUrl(data.data.public_url)}
            />

            <div className="border-t border-gray-100 pt-4">
                {confirmingRotate ? (
                    <div className="flex flex-wrap items-center gap-3">
                        <span className="text-sm text-gray-700">
                            O link atual deixará de funcionar. Continuar?
                        </span>
                        <Button
                            type="button"
                            variant="destructive"
                            className="px-3 py-2"
                            disabled={rotateMutation.isPending}
                            onClick={() => rotateMutation.mutate()}
                        >
                            {rotateMutation.isPending && (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            )}
                            Confirmar
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            className="px-3 py-2"
                            onClick={() => setConfirmingRotate(false)}
                        >
                            Cancelar
                        </Button>
                    </div>
                ) : (
                    <Button
                        type="button"
                        variant="outline"
                        className="px-3 py-2"
                        onClick={() => setConfirmingRotate(true)}
                    >
                        <RefreshCw className="h-4 w-4" />
                        Gerar novo link
                    </Button>
                )}
                {rotateMutation.isError && (
                    <p className="mt-2 text-sm text-red-600">
                        Não foi possível gerar um novo link.
                    </p>
                )}
            </div>
        </div>
    );
}
