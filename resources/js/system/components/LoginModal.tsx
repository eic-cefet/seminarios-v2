import { useState } from "react";
import { Link } from "react-router-dom";
import * as Dialog from "@radix-ui/react-dialog";
import * as Label from "@radix-ui/react-label";
import { X } from "lucide-react";
import { cn } from "@shared/lib/utils";
import { useAuth } from "@shared/contexts/AuthContext";
import { authApi } from "@shared/api/client";
import { getErrorMessage } from "@shared/lib/errors";
import { GoogleIcon, GithubIcon } from "@shared/components/icons/SocialIcons";

interface LoginModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function LoginModal({ open, onOpenChange }: LoginModalProps) {
    const { login } = useAuth();
    const [view, setView] = useState<"login" | "forgot">("login");
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [forgotEmail, setForgotEmail] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [forgotSuccess, setForgotSuccess] = useState(false);

    const handleLogin = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            await login(email, password, true);
            onOpenChange(false);
        } catch (err) {
            setError(getErrorMessage(err));
        } finally {
            setLoading(false);
        }
    };

    const handleForgotPassword = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            await authApi.forgotPassword(forgotEmail);
            setForgotSuccess(true);
        } catch (err) {
            setError(getErrorMessage(err));
        } finally {
            setLoading(false);
        }
    };

    const handleSocialLogin = (provider: "google" | "github") => {
        window.location.href = `/auth/${provider}`;
    };

    const resetModal = () => {
        setView("login");
        setEmail("");
        setPassword("");
        setForgotEmail("");
        setError(null);
        setForgotSuccess(false);
    };

    return (
        <Dialog.Root
            open={open}
            onOpenChange={(newOpen) => {
                if (!newOpen) resetModal();
                onOpenChange(newOpen);
            }}
        >
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" />
                <Dialog.Content className="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-lg shadow-xl p-6 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%]">
                    <Dialog.Close asChild>
                        <button
                            className="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-white transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-offset-2 cursor-pointer"
                            aria-label="Fechar"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </Dialog.Close>

                    {view === "login" ? (
                        <>
                            <Dialog.Title className="text-xl font-semibold text-gray-900">
                                Entrar
                            </Dialog.Title>
                            <Dialog.Description className="mt-1 text-sm text-gray-500">
                                Entre com sua conta para acessar o sistema
                            </Dialog.Description>

                            {/* Social Login Buttons */}
                            <div className="mt-6 space-y-3">
                                <button
                                    type="button"
                                    onClick={() => handleSocialLogin("google")}
                                    className="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors cursor-pointer"
                                >
                                    <GoogleIcon className="h-5 w-5" />
                                    Continuar com Google
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleSocialLogin("github")}
                                    className="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors cursor-pointer"
                                >
                                    <GithubIcon className="h-5 w-5" />
                                    Continuar com GitHub
                                </button>
                            </div>

                            <div className="relative my-6">
                                <div className="absolute inset-0 flex items-center">
                                    <div className="w-full border-t border-gray-200" />
                                </div>
                                <div className="relative flex justify-center text-sm">
                                    <span className="bg-white px-2 text-gray-500">
                                        ou
                                    </span>
                                </div>
                            </div>

                            {/* Email/Password Form */}
                            <form onSubmit={handleLogin} className="space-y-4">
                                {error && (
                                    <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                        {error}
                                    </div>
                                )}

                                <div>
                                    <Label.Root
                                        htmlFor="email"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        E-mail
                                    </Label.Root>
                                    <input
                                        id="email"
                                        type="email"
                                        value={email}
                                        onChange={(e) =>
                                            setEmail(e.target.value)
                                        }
                                        required
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                        placeholder="seu@email.com"
                                    />
                                </div>

                                <div>
                                    <div className="flex items-center justify-between">
                                        <Label.Root
                                            htmlFor="password"
                                            className="block text-sm font-medium text-gray-700"
                                        >
                                            Senha
                                        </Label.Root>
                                        <button
                                            type="button"
                                            onClick={() => setView("forgot")}
                                            className="text-sm text-primary-600 hover:text-primary-700 cursor-pointer"
                                        >
                                            Esqueci minha senha
                                        </button>
                                    </div>
                                    <input
                                        id="password"
                                        type="password"
                                        value={password}
                                        onChange={(e) =>
                                            setPassword(e.target.value)
                                        }
                                        required
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                        placeholder="••••••••"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    disabled={loading}
                                    className={cn(
                                        "w-full rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors cursor-pointer",
                                        loading &&
                                            "opacity-70 cursor-not-allowed",
                                    )}
                                >
                                    {loading ? "Entrando..." : "Entrar"}
                                </button>
                            </form>

                            <p className="mt-4 text-center text-sm text-gray-500">
                                Não tem uma conta?{" "}
                                <Link
                                    to="/cadastro"
                                    onClick={() => onOpenChange(false)}
                                    className="font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                                >
                                    Cadastre-se
                                </Link>
                            </p>
                        </>
                    ) : (
                        <>
                            <Dialog.Title className="text-xl font-semibold text-gray-900">
                                Recuperar senha
                            </Dialog.Title>
                            <Dialog.Description className="mt-1 text-sm text-gray-500">
                                Digite seu e-mail para receber um link de
                                recuperação
                            </Dialog.Description>

                            {forgotSuccess ? (
                                <div className="mt-6 space-y-4">
                                    <div className="rounded-md bg-green-50 p-4 text-sm text-green-700">
                                        Se o e-mail informado estiver
                                        cadastrado, você receberá um link para
                                        redefinir sua senha.
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setView("login");
                                            setForgotSuccess(false);
                                        }}
                                        className="w-full rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors cursor-pointer"
                                    >
                                        Voltar para login
                                    </button>
                                </div>
                            ) : (
                                <form
                                    onSubmit={handleForgotPassword}
                                    className="mt-6 space-y-4"
                                >
                                    {error && (
                                        <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                            {error}
                                        </div>
                                    )}

                                    <div>
                                        <Label.Root
                                            htmlFor="forgot-email"
                                            className="block text-sm font-medium text-gray-700"
                                        >
                                            E-mail
                                        </Label.Root>
                                        <input
                                            id="forgot-email"
                                            type="email"
                                            value={forgotEmail}
                                            onChange={(e) =>
                                                setForgotEmail(e.target.value)
                                            }
                                            required
                                            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                            placeholder="seu@email.com"
                                        />
                                    </div>

                                    <div className="flex gap-3">
                                        <button
                                            type="button"
                                            onClick={() => setView("login")}
                                            className="flex-1 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors cursor-pointer"
                                        >
                                            Voltar
                                        </button>
                                        <button
                                            type="submit"
                                            disabled={loading}
                                            className={cn(
                                                "flex-1 rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors cursor-pointer",
                                                loading &&
                                                    "opacity-70 cursor-not-allowed",
                                            )}
                                        >
                                            {loading ? "Enviando..." : "Enviar"}
                                        </button>
                                    </div>
                                </form>
                            )}
                        </>
                    )}
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
