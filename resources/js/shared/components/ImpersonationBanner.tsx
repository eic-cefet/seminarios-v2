import { useMutation, useQueryClient } from "@tanstack/react-query";
import { ArrowLeft, UserRound } from "lucide-react";
import { toast } from "sonner";
import { authApi } from "@shared/api/client";
import { useAuth } from "@shared/contexts/AuthContext";
import { buildUrl } from "@shared/lib/utils";

export function ImpersonationBanner() {
    const { user } = useAuth();
    const queryClient = useQueryClient();
    const stop = useMutation({
        mutationFn: () => authApi.stopImpersonation(),
        onSuccess: () => {
            queryClient.clear();
            window.location.href = buildUrl("/admin/users");
        },
        onError: () => toast.error("Não foi possível voltar ao administrador. Tente novamente."),
    });

    if (!user?.is_impersonating) return null;

    return (
        <section aria-label="Acesso como usuário"
            className="sticky top-0 z-50 flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
            <div className="flex min-w-0 items-center gap-2">
                <UserRound className="size-4 shrink-0" aria-hidden="true" />
                <span className="break-words">Acessando como <strong>{user.name}</strong></span>
            </div>
            <button type="button" disabled={stop.isPending} onClick={() => stop.mutate()}
                className="inline-flex shrink-0 items-center gap-2 rounded-md border border-amber-400 px-3 py-2 font-medium hover:bg-amber-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700 disabled:cursor-wait disabled:opacity-60 dark:border-amber-700 dark:hover:bg-amber-900">
                <ArrowLeft className="size-4" aria-hidden="true" />
                {stop.isPending ? "Voltando..." : "Voltar ao administrador"}
            </button>
        </section>
    );
}
