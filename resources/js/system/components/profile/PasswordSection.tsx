import { profileApi } from "@shared/api/client";
import { getErrorMessage, getFieldErrors } from "@shared/lib/errors";
import { cn } from "@shared/lib/utils";
import { analytics } from "@shared/lib/analytics";
import { useMutation } from "@tanstack/react-query";
import { Check, Lock } from "lucide-react";
import { useState } from "react";

export function PasswordSection() {
    const [isEditing, setIsEditing] = useState(false);
    const [currentPassword, setCurrentPassword] = useState("");
    const [password, setPassword] = useState("");
    const [passwordConfirmation, setPasswordConfirmation] = useState("");
    const [error, setError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [success, setSuccess] = useState(false);

    const mutation = useMutation({
        mutationFn: () =>
            profileApi.updatePassword({
                current_password: currentPassword,
                password,
                password_confirmation: passwordConfirmation,
            }),
        onSuccess: () => {
            setError(null);
            setFieldErrors({});
            setSuccess(true);
            setIsEditing(false);
            setCurrentPassword("");
            setPassword("");
            setPasswordConfirmation("");
            analytics.event("profile_password_change");
            setTimeout(() => setSuccess(false), 3000);
        },
        onError: (err) => {
            setError(getErrorMessage(err));
            setFieldErrors(getFieldErrors(err) || {});
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate();
    };

    const handleCancel = () => {
        setCurrentPassword("");
        setPassword("");
        setPasswordConfirmation("");
        setError(null);
        setFieldErrors({});
        setIsEditing(false);
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
                            onClick={() => setIsEditing(true)}
                            className="text-sm font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                        >
                            Alterar senha
                        </button>
                    )}
                </div>
            </div>

            <div className="px-6 py-4">
                {success && (
                    <div className="flex items-center gap-2 rounded-md bg-green-50 p-3 text-sm text-green-700">
                        <Check className="h-4 w-4" />
                        Senha atualizada com sucesso!
                    </div>
                )}

                {isEditing ? (
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {error && (
                            <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                {error}
                            </div>
                        )}

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
