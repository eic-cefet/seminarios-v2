import { useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { PageTitle } from "@shared/components/PageTitle";
import { Button } from "../../components/ui/button";
import { Input } from "../../components/ui/input";
import { Label } from "../../components/ui/label";
import {
    AdminApiError,
    envSecretsApi,
    type AdminEnvSecretsUpdatePayload,
    type AdminEnvSecretsUpdateResult,
} from "../../api/adminClient";
import NotFound from "../NotFound";

const envSecretsSchema = z
    .object({
        secret_id: z
            .string()
            .min(1, "Informe o ID do secret")
            .max(2048)
            .regex(/^[A-Za-z0-9_+=,.@:/-]+$/, "Caracteres inválidos no ID do secret"),
        region: z
            .string()
            .max(64)
            .regex(/^[a-z0-9-]*$/, "Região inválida (ex: us-east-1)"),
        access_key_id: z
            .string()
            .max(128)
            .regex(/^[A-Za-z0-9]*$/, "Access key inválida"),
        secret_access_key: z
            .string()
            .max(256)
            .regex(/^[A-Za-z0-9/+=]*$/, "Secret access key inválida"),
    })
    .refine(
        (data) => (data.access_key_id === "") === (data.secret_access_key === ""),
        {
            message: "Informe a access key e a secret access key juntas",
            path: ["secret_access_key"],
        },
    );

type EnvSecretsFormData = z.infer<typeof envSecretsSchema>;

function toPayload(data: EnvSecretsFormData): AdminEnvSecretsUpdatePayload {
    return {
        secret_id: data.secret_id,
        region: data.region || undefined,
        access_key_id: data.access_key_id || undefined,
        secret_access_key: data.secret_access_key || undefined,
    };
}

function updateErrorMessage(error: unknown): string {
    if (error instanceof AdminApiError) {
        const fieldErrors = Object.values(error.errors ?? {}).flat();
        return fieldErrors[0] ?? error.message;
    }

    return "Não foi possível aplicar as configurações.";
}

export default function EnvSecrets() {
    const [confirming, setConfirming] = useState(false);
    const [pendingData, setPendingData] = useState<EnvSecretsFormData | null>(
        null,
    );
    const [result, setResult] = useState<AdminEnvSecretsUpdateResult | null>(
        null,
    );

    const { data, isLoading, error } = useQuery({
        queryKey: ["admin", "env-secrets"],
        queryFn: () => envSecretsApi.get(),
        retry: false,
    });

    const status = data?.data;

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<EnvSecretsFormData>({
        resolver: zodResolver(envSecretsSchema),
        values: status
            ? {
                  secret_id: status.secret_id ?? "",
                  region: status.region ?? "",
                  access_key_id: "",
                  secret_access_key: "",
              }
            : undefined,
        defaultValues: {
            secret_id: "",
            region: "",
            access_key_id: "",
            secret_access_key: "",
        },
    });

    const updateMutation = useMutation({
        mutationFn: (payload: AdminEnvSecretsUpdatePayload) =>
            envSecretsApi.update(payload),
        onSuccess: (response) => {
            setResult(response.data);
            setConfirming(false);
            setPendingData(null);
        },
    });

    if (isLoading) {
        return (
            <div
                role="status"
                aria-label="Carregando"
                className="p-6 text-muted-foreground"
            >
                Carregando…
            </div>
        );
    }

    if (error instanceof AdminApiError && error.status === 404) {
        return <NotFound />;
    }

    if (error || !status) {
        return (
            <p className="p-6 text-destructive">
                Não foi possível carregar as configurações.
            </p>
        );
    }

    const onSubmit = (formData: EnvSecretsFormData) => {
        updateMutation.reset();
        setResult(null);
        setPendingData(formData);
        setConfirming(true);
    };

    const confirm = () => {
        /* v8 ignore next -- @preserve defensive guard: confirming is only reachable with pendingData set */
        if (pendingData) {
            updateMutation.mutate(toPayload(pendingData));
        }
    };

    return (
        <div className="mx-auto max-w-2xl space-y-6 p-6">
            <PageTitle title="Env Secrets (AWS)" />
            <div>
                <h1 className="text-2xl font-semibold text-foreground">
                    Env Secrets (AWS)
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Configura o secret do AWS Secrets Manager que sobrescreve
                    variáveis de ambiente. A validação busca o secret antes de
                    aplicar qualquer mudança.
                </p>
                <p className="mt-1 text-sm text-muted-foreground">
                    {status.applied
                        ? "A configuração atual já está aplicada no cache de config."
                        : "Há configuração no .env ainda não refletida no cache de config."}
                </p>
            </div>

            <form
                onSubmit={handleSubmit(onSubmit)}
                noValidate
                className="space-y-4"
            >
                <div className="space-y-1">
                    <Label htmlFor="secret_id">Secret ID (nome ou ARN) *</Label>
                    <Input id="secret_id" type="text" {...register("secret_id")} />
                    {errors.secret_id && (
                        <p className="text-sm text-destructive">
                            {errors.secret_id.message}
                        </p>
                    )}
                </div>

                <div className="space-y-1">
                    <Label htmlFor="region">Região (opcional)</Label>
                    <Input
                        id="region"
                        type="text"
                        placeholder="us-east-1"
                        {...register("region")}
                    />
                    {errors.region && (
                        <p className="text-sm text-destructive">
                            {errors.region.message}
                        </p>
                    )}
                </div>

                <div className="space-y-1">
                    <Label htmlFor="access_key_id">Access Key ID (opcional)</Label>
                    <Input
                        id="access_key_id"
                        type="text"
                        autoComplete="off"
                        {...register("access_key_id")}
                    />
                    <p className="text-xs text-muted-foreground">
                        {status.access_key_id_set
                            ? "Access key configurada no servidor (não exibida). Deixe em branco para removê-la ao aplicar."
                            : "Nenhuma access key dedicada configurada."}
                    </p>
                    {errors.access_key_id && (
                        <p className="text-sm text-destructive">
                            {errors.access_key_id.message}
                        </p>
                    )}
                </div>

                <div className="space-y-1">
                    <Label htmlFor="secret_access_key">
                        Secret Access Key (opcional)
                    </Label>
                    <Input
                        id="secret_access_key"
                        type="password"
                        autoComplete="off"
                        {...register("secret_access_key")}
                    />
                    <p className="text-xs text-muted-foreground">
                        {status.secret_access_key_set
                            ? "Secret access key configurada no servidor (não exibida)."
                            : "Nenhuma secret access key dedicada configurada."}
                    </p>
                    {errors.secret_access_key && (
                        <p className="text-sm text-destructive">
                            {errors.secret_access_key.message}
                        </p>
                    )}
                </div>

                {!confirming && <Button type="submit">Validar e aplicar</Button>}
            </form>

            {confirming && (
                <div className="rounded-md border border-amber-300 bg-amber-50 p-4">
                    <p className="text-sm text-amber-800">
                        Isso vai reescrever o .env, refazer o cache de
                        configuração e reiniciar os workers da fila. O secret
                        será validado na AWS antes de qualquer mudança.
                        Continuar?
                    </p>
                    <div className="mt-3 flex gap-3">
                        <Button
                            type="button"
                            onClick={confirm}
                            disabled={updateMutation.isPending}
                            className="bg-amber-600 text-white hover:bg-amber-700"
                        >
                            {updateMutation.isPending
                                ? "Aplicando…"
                                : "Confirmar"}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setConfirming(false)}
                            disabled={updateMutation.isPending}
                        >
                            Cancelar
                        </Button>
                    </div>
                </div>
            )}

            {updateMutation.isError && (
                <p className="text-sm text-destructive">
                    {updateErrorMessage(updateMutation.error)}
                </p>
            )}

            {result && (
                <div className="rounded-md border border-green-300 bg-green-50 p-4">
                    <p className="text-sm font-medium text-green-800">
                        Configuração aplicada. O secret fornece {result.count}{" "}
                        variáveis:
                    </p>
                    <ul className="mt-2 list-inside list-disc text-sm text-green-800">
                        {result.keys.map((key) => (
                            <li key={key}>{key}</li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}
