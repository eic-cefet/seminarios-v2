import { profileApi } from "@shared/api/client";
import { useAuth } from "@shared/contexts/AuthContext";
import { FULL_NAME_MESSAGE, isFullName } from "@shared/lib/fullName";
import { useMutation } from "@tanstack/react-query";
import { useState } from "react";
import { useNavigate } from "react-router-dom";

export function CompleteProfile() {
    const navigate = useNavigate();
    const { user, refreshUser } = useAuth();
    const [name, setName] = useState(user?.name ?? "");
    const [error, setError] = useState<string | null>(null);

    const mutation = useMutation({
        mutationFn: (fullName: string) =>
            profileApi.update({ name: fullName, email: user?.email ?? "" }),
        onSuccess: async () => {
            await refreshUser();
            navigate("/", { replace: true });
        },
    });

    const onSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        if (!isFullName(name)) {
            setError(FULL_NAME_MESSAGE);
            return;
        }
        setError(null);
        mutation.mutate(name.trim());
    };

    return (
        <main className="mx-auto flex min-h-screen max-w-md flex-col justify-center px-4 py-12">
            <h1 className="text-2xl font-semibold text-gray-900">
                Complete seu cadastro
            </h1>
            <p className="mt-2 text-sm text-gray-600">
                Precisamos do seu nome completo para emitir o certificado
                correto.
            </p>
            <form onSubmit={onSubmit} className="mt-6 flex flex-col gap-4">
                <label
                    htmlFor="complete-profile-name"
                    className="flex flex-col gap-1 text-sm font-medium text-gray-700"
                >
                    Nome completo
                    <input
                        id="complete-profile-name"
                        type="text"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        autoComplete="name"
                        aria-invalid={error ? true : undefined}
                        aria-describedby={
                            error ? "complete-profile-name-error" : undefined
                        }
                        className="rounded-md border border-gray-300 px-3 py-2 text-base text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                </label>
                {error && (
                    <p
                        id="complete-profile-name-error"
                        role="alert"
                        className="text-sm text-red-600"
                    >
                        {error}
                    </p>
                )}
                <button
                    type="submit"
                    disabled={mutation.isPending}
                    className="rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 disabled:opacity-60"
                >
                    {mutation.isPending ? "Salvando..." : "Continuar"}
                </button>
            </form>
        </main>
    );
}
