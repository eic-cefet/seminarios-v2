import { profileApi } from "@shared/api/client";
import { cn } from "@shared/lib/utils";
import { analytics } from "@shared/lib/analytics";
import { FormField } from "@shared/components/FormField";
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

                        <FormField
                            id="name"
                            label="Nome"
                            type="text"
                            autoComplete="name"
                            required
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            error={fieldErrors.name}
                        />
                        <FormField
                            id="email"
                            label="E-mail"
                            type="email"
                            autoComplete="email"
                            required
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            error={fieldErrors.email}
                        />

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
