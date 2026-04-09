import { profileApi } from "@shared/api/client";
import { cn } from "@shared/lib/utils";
import { analytics } from "@shared/lib/analytics";
import { useMutation } from "@tanstack/react-query";
import { Lock } from "lucide-react";
import { useState } from "react";
import { ErrorAlert, SuccessAlert } from "./FormAlerts";
import { useProfileForm } from "./useProfileForm";

export function PasswordSection() {
    const [currentPassword, setCurrentPassword] = useState("");
    const [password, setPassword] = useState("");
    const [passwordConfirmation, setPasswordConfirmation] = useState("");

    const clearPasswords = () => {
        setCurrentPassword("");
        setPassword("");
        setPasswordConfirmation("");
    };

    const { isEditing, startEditing, error, fieldErrors, success, mutationCallbacks, handleCancel } =
        useProfileForm({
            onSuccess: () => {
                clearPasswords();
                analytics.event("profile_password_change");
            },
            onCancel: clearPasswords,
        });

    const mutation = useMutation({
        mutationFn: () =>
            profileApi.updatePassword({
                current_password: currentPassword,
                password,
                password_confirmation: passwordConfirmation,
            }),
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
                        <Lock className="h-5 w-5 text-gray-400" />
                        <h2 className="text-lg font-semibold text-gray-900">
                            Senha
                        </h2>
                    </div>
                    {!isEditing && (
                        <button
                            onClick={startEditing}
                            className="text-sm font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                        >
                            Alterar senha
                        </button>
                    )}
                </div>
            </div>

            <div className="px-6 py-4">
                {success && (
                    <SuccessAlert message="Senha atualizada com sucesso!" />
                )}

                {isEditing ? (
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {error && <ErrorAlert message={error} />}

                        <div>
                            <label
                                htmlFor="current-password"
                                className="block text-sm font-medium text-gray-700"
                            >
                                Senha atual
                            </label>
                            <input
                                id="current-password"
                                type="password"
                                value={currentPassword}
                                onChange={(e) =>
                                    setCurrentPassword(e.target.value)
                                }
                                required
                                className={cn(
                                    "mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1",
                                    fieldErrors.current_password
                                        ? "border-red-300 focus:border-red-500 focus:ring-red-500"
                                        : "border-gray-300 focus:border-primary-500 focus:ring-primary-500",
                                )}
                            />
                            {fieldErrors.current_password && (
                                <p className="mt-1 text-sm text-red-600">
                                    {fieldErrors.current_password}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="new-password"
                                className="block text-sm font-medium text-gray-700"
                            >
                                Nova senha
                            </label>
                            <input
                                id="new-password"
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                required
                                className={cn(
                                    "mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1",
                                    fieldErrors.password
                                        ? "border-red-300 focus:border-red-500 focus:ring-red-500"
                                        : "border-gray-300 focus:border-primary-500 focus:ring-primary-500",
                                )}
                            />
                            {fieldErrors.password && (
                                <p className="mt-1 text-sm text-red-600">
                                    {fieldErrors.password}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="password-confirmation"
                                className="block text-sm font-medium text-gray-700"
                            >
                                Confirmar nova senha
                            </label>
                            <input
                                id="password-confirmation"
                                type="password"
                                value={passwordConfirmation}
                                onChange={(e) =>
                                    setPasswordConfirmation(e.target.value)
                                }
                                required
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                            />
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
                                {mutation.isPending
                                    ? "Salvando..."
                                    : "Alterar senha"}
                            </button>
                        </div>
                    </form>
                ) : (
                    !success && (
                        <p className="text-sm text-gray-500">
                            Use uma senha forte com pelo menos 8 caracteres.
                        </p>
                    )
                )}
            </div>
        </section>
    );
}
