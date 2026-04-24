import { useRef, useState } from "react";
import { Link, useLocation } from "react-router-dom";
import * as Dialog from "@radix-ui/react-dialog";
import { X } from "lucide-react";
import { SocialLoginButtons } from "@shared/components/SocialLoginButtons";
import { FormField } from "@shared/components/FormField";
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSeparator,
    InputOTPSlot,
} from "@shared/components/InputOTP";
import { ROUTES } from "@shared/config/routes";
import { buildUrl, cn } from "@shared/lib/utils";
import { useAuth } from "@shared/contexts/AuthContext";
import { authApi } from "@shared/api/client";
import { twoFactorApi } from "@shared/api/twoFactorApi";
import { getErrorMessage } from "@shared/lib/errors";

interface LoginModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function LoginModal({ open, onOpenChange }: LoginModalProps) {
    const { login, completeTwoFactor } = useAuth();
    const location = useLocation();
    const [view, setView] = useState<"login" | "forgot" | "two_factor">("login");
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [forgotEmail, setForgotEmail] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [forgotSuccess, setForgotSuccess] = useState(false);
    const [challengeToken, setChallengeToken] = useState<string | null>(null);
    const [twoFactorCode, setTwoFactorCode] = useState("");
    const [recoveryCode, setRecoveryCode] = useState("");
    const [useRecovery, setUseRecovery] = useState(false);
    const [rememberDevice, setRememberDevice] = useState(false);
    const emailRef = useRef<HTMLInputElement>(null);
    const twoFactorCodeRef = useRef<HTMLInputElement>(null);

    const handleLogin = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const result = await login(email, password, true);
            if (result?.status === "two_factor_required") {
                setChallengeToken(result.challengeToken);
                setView("two_factor");
                setTimeout(() => twoFactorCodeRef.current?.focus(), 0);
                return;
            }
            onOpenChange(false);
        } catch (err) {
            setError(getErrorMessage(err));
        } finally {
            setLoading(false);
        }
    };

    const handleTwoFactor = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const response = await twoFactorApi.challenge({
                challenge_token: challengeToken!,
                ...(useRecovery
                    ? { recovery_code: recoveryCode }
                    : { code: twoFactorCode }),
                remember_device: rememberDevice,
            });
            completeTwoFactor(response.user);
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
        const currentPath = location.pathname + location.search;
        if (currentPath !== "/") {
            sessionStorage.setItem("auth_redirect", currentPath);
        }
        window.location.href = buildUrl(`/auth/${provider}`);
    };

    const resetModal = () => {
        setView("login");
        setEmail("");
        setPassword("");
        setForgotEmail("");
        setError(null);
        setForgotSuccess(false);
        setChallengeToken(null);
        setTwoFactorCode("");
        setRecoveryCode("");
        setUseRecovery(false);
        setRememberDevice(false);
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
                <Dialog.Content
                    onOpenAutoFocus={(e) => {
                        e.preventDefault();
                        emailRef.current?.focus();
                    }}
                    className="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-lg shadow-xl p-6 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%]"
                >
                    <Dialog.Close asChild>
                        <button
                            className="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-white transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-offset-2 cursor-pointer"
                            aria-label="Fechar"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </Dialog.Close>

                    {view === "login" && (
                        <>
                            <Dialog.Title className="text-xl font-semibold text-gray-900">
                                Entrar
                            </Dialog.Title>
                            <Dialog.Description className="mt-1 text-sm text-gray-500">
                                Entre com sua conta para acessar o sistema
                            </Dialog.Description>

                            <div className="mt-6 space-y-6">
                                <SocialLoginButtons
                                    onSocialLogin={handleSocialLogin}
                                    actionLabel="Continuar"
                                />
                            </div>

                            <form onSubmit={handleLogin} className="space-y-4">
                                {error && (
                                    <div role="alert" className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                        {error}
                                    </div>
                                )}

                                <FormField
                                    ref={emailRef}
                                    id="login-modal-email"
                                    name="email"
                                    type="email"
                                    label="E-mail"
                                    autoComplete="email"
                                    required
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="seu@email.com"
                                />

                                <div>
                                    <div className="flex items-center justify-between mb-1">
                                        <span className="block text-sm font-medium text-gray-700" aria-hidden="true">
                                            Senha
                                        </span>
                                        <button
                                            type="button"
                                            onClick={() => setView("forgot")}
                                            className="text-sm text-primary-600 hover:text-primary-700 cursor-pointer"
                                        >
                                            Esqueci minha senha
                                        </button>
                                    </div>
                                    <FormField
                                        id="login-modal-password"
                                        name="password"
                                        type="password"
                                        label="Senha"
                                        labelClassName="sr-only"
                                        autoComplete="current-password"
                                        required
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
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
                                    to={ROUTES.SYSTEM.REGISTER}
                                    onClick={() => onOpenChange(false)}
                                    className="font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                                >
                                    Cadastre-se
                                </Link>
                            </p>
                        </>
                    )}

                    {view === "two_factor" && (
                        <>
                            <Dialog.Title className="text-xl font-semibold text-gray-900">
                                Verificação em duas etapas
                            </Dialog.Title>
                            <Dialog.Description className="mt-1 text-sm text-gray-500">
                                {useRecovery
                                    ? "Insira um dos códigos de recuperação que você guardou."
                                    : "Insira o código de 6 dígitos do seu app autenticador."}
                            </Dialog.Description>

                            <form onSubmit={handleTwoFactor} className="mt-6 space-y-4">
                                {error && (
                                    <div role="alert" className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                        {error}
                                    </div>
                                )}

                                {useRecovery ? (
                                    <FormField
                                        id="login-modal-recovery-code"
                                        name="recovery_code"
                                        type="text"
                                        label="Código de recuperação"
                                        autoComplete="one-time-code"
                                        required
                                        value={recoveryCode}
                                        onChange={(e) => setRecoveryCode(e.target.value)}
                                    />
                                ) : (
                                    <div className="flex flex-col items-center gap-2">
                                        <label
                                            htmlFor="login-modal-two-factor-code"
                                            className="sr-only"
                                        >
                                            Código
                                        </label>
                                        <InputOTP
                                            ref={twoFactorCodeRef}
                                            id="login-modal-two-factor-code"
                                            maxLength={6}
                                            value={twoFactorCode}
                                            onChange={setTwoFactorCode}
                                            autoComplete="one-time-code"
                                            autoFocus
                                        >
                                            <InputOTPGroup>
                                                <InputOTPSlot index={0} />
                                                <InputOTPSlot index={1} />
                                                <InputOTPSlot index={2} />
                                            </InputOTPGroup>
                                            <InputOTPSeparator />
                                            <InputOTPGroup>
                                                <InputOTPSlot index={3} />
                                                <InputOTPSlot index={4} />
                                                <InputOTPSlot index={5} />
                                            </InputOTPGroup>
                                        </InputOTP>
                                    </div>
                                )}

                                <label className="flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={rememberDevice}
                                        onChange={(e) => setRememberDevice(e.target.checked)}
                                        className="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    Lembrar este dispositivo por 30 dias
                                </label>

                                <div className="flex gap-3">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setView("login");
                                            setError(null);
                                            setTwoFactorCode("");
                                            setRecoveryCode("");
                                            setUseRecovery(false);
                                            setChallengeToken(null);
                                        }}
                                        className="flex-1 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors cursor-pointer"
                                    >
                                        Voltar
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={loading}
                                        className={cn(
                                            "flex-1 rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors cursor-pointer",
                                            loading && "opacity-70 cursor-not-allowed",
                                        )}
                                    >
                                        {loading ? "Verificando..." : "Verificar"}
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    onClick={() => {
                                        setUseRecovery((v) => !v);
                                        setTwoFactorCode("");
                                        setRecoveryCode("");
                                        setError(null);
                                    }}
                                    className="w-full text-center text-sm font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                                >
                                    {useRecovery
                                        ? "Usar aplicativo autenticador"
                                        : "Usar código de recuperação"}
                                </button>
                            </form>
                        </>
                    )}

                    {view === "forgot" && (
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
                                        <div role="alert" className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                            {error}
                                        </div>
                                    )}

                                    <FormField
                                        id="login-modal-forgot-email"
                                        name="email"
                                        type="email"
                                        label="E-mail"
                                        autoComplete="email"
                                        required
                                        value={forgotEmail}
                                        onChange={(e) =>
                                            setForgotEmail(e.target.value)
                                        }
                                        placeholder="seu@email.com"
                                    />

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
