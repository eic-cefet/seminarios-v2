import { profileApi } from "@shared/api/client";
import { cn } from "@shared/lib/utils";
import { analytics } from "@shared/lib/analytics";
import { useMutation } from "@tanstack/react-query";
import { Mail, User } from "lucide-react";
import { useState } from "react";
import { ErrorAlert, SuccessAlert } from "./FormAlerts";
import { useProfileForm } from "./useProfileForm";

interface ProfileInfoSectionProps {
    user: { id: number; name: string; email: string };
    onUpdate: () => Promise<void>;
}

export function ProfileInfoSection({ user, onUpdate }: ProfileInfoSectionProps) {
    const [name, setName] = useState(user.name);
    const [email, setEmail] = useState(user.email);

    const { isEditing, startEditing, error, fieldErrors, success, mutationCallbacks, handleCancel } =
        useProfileForm({
            onSuccess: async () => {
                analytics.event("profile_info_update");
                await onUpdate();
            },
            onCancel: () => {
                setName(user.name);
                setEmail(user.email);
            },
        });

    const mutation = useMutation({
        mutationFn: () => profileApi.update({ name, email }),
        ...mutationCallbacks,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate();
    };

    return (
        <section className="rounded-lg border border-gray-200 bg-white">
            <div className="border-b border-gray-200 px-6 py-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <User className="h-5 w-5 text-gray-400" />
                        <h2 className="text-lg font-semibold text-gray-900">
                            Informações pessoais
                        </h2>
                    </div>
                    {!isEditing && (
                        <button
                            onClick={startEditing}
                            className="text-sm font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                        >
                            Editar
                        </button>
                    )}
                </div>
            </div>

            <div className="px-6 py-4">
                {success && (
                    <SuccessAlert message="Perfil atualizado com sucesso!" />
                )}

                {isEditing ? (
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {error && <ErrorAlert message={error} />}

                        <div>
                            <label
                                htmlFor="name"
                                className="block text-sm font-medium text-gray-700"
                            >
                                Nome
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                required
                                className={cn(
                                    "mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1",
                                    fieldErrors.name
                                        ? "border-red-300 focus:border-red-500 focus:ring-red-500"
                                        : "border-gray-300 focus:border-primary-500 focus:ring-primary-500",
                                )}
                            />
                            {fieldErrors.name && (
                                <p className="mt-1 text-sm text-red-600">
                                    {fieldErrors.name}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="email"
                                className="block text-sm font-medium text-gray-700"
                            >
                                E-mail
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                required
                                className={cn(
                                    "mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1",
                                    fieldErrors.email
                                        ? "border-red-300 focus:border-red-500 focus:ring-red-500"
                                        : "border-gray-300 focus:border-primary-500 focus:ring-primary-500",
                                )}
                            />
                            {fieldErrors.email && (
                                <p className="mt-1 text-sm text-red-600">
                                    {fieldErrors.email}
                                </p>
                            )}
                        </div>

                        <div className="flex gap-3 pt-2">
                            <button
                                type="button"
                                onClick={handleCancel}
                                className="flex-1 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors cursor-pointer"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                disabled={mutation.isPending}
                                className={cn(
                                    "flex-1 rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer",
                                    mutation.isPending &&
                                        "opacity-70 cursor-not-allowed",
                                )}
                            >
                                {mutation.isPending ? "Salvando..." : "Salvar"}
                            </button>
                        </div>
                    </form>
                ) : (
                    <dl className="space-y-4">
                        <div className="flex items-center gap-3">
                            <dt className="flex items-center gap-2 text-sm text-gray-500 w-24">
                                <User className="h-4 w-4" />
                                Nome
                            </dt>
                            <dd className="text-sm text-gray-900">
                                {user.name}
                            </dd>
                        </div>
                        <div className="flex items-center gap-3">
                            <dt className="flex items-center gap-2 text-sm text-gray-500 w-24">
                                <Mail className="h-4 w-4" />
                                E-mail
                            </dt>
                            <dd className="text-sm text-gray-900">
                                {user.email}
                            </dd>
                        </div>
                    </dl>
                )}
            </div>
        </section>
    );
}
