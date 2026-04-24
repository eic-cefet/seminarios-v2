import { useEffect, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { Layout } from "../components/Layout";
import { FormField } from "@shared/components/FormField";
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSeparator,
    InputOTPSlot,
} from "@shared/components/InputOTP";
import { PageTitle } from "@shared/components/PageTitle";
import { ROUTES } from "@shared/config/routes";
import { useAuth } from "@shared/contexts/AuthContext";
import { twoFactorApi } from "@shared/api/twoFactorApi";
import { getErrorMessage } from "@shared/lib/errors";
import { isSafeRedirect, cn } from "@shared/lib/utils";

type LocationState = {
    challengeToken?: string;
    remember?: boolean;
    from?: string;
};

export default function TwoFactorChallenge() {
    const navigate = useNavigate();
    const location = useLocation();
    const { completeTwoFactor } = useAuth();
    const state = (location.state || {}) as LocationState;
    const challengeToken = state.challengeToken;

    const fromRaw = state.from || "/";
    const redirectTo = isSafeRedirect(fromRaw) ? fromRaw : "/";

    useEffect(() => {
        if (!challengeToken) {
            navigate(ROUTES.SYSTEM.LOGIN, { replace: true });
        }
    }, [challengeToken, navigate]);

    const [useRecovery, setUseRecovery] = useState(false);
    const [code, setCode] = useState("");
    const [recoveryCode, setRecoveryCode] = useState("");
    const [rememberDevice, setRememberDevice] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setLoading(true);
        try {
            const response = await twoFactorApi.challenge({
                challenge_token: challengeToken!,
                ...(useRecovery
                    ? { recovery_code: recoveryCode }
                    : { code }),
                remember_device: rememberDevice,
            });
            completeTwoFactor(response.user);
            navigate(redirectTo, { replace: true });
        } catch (err) {
            setError(getErrorMessage(err));
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <PageTitle title="Verificação em duas etapas" />
            <Layout>
                <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
                    <div className="w-full max-w-md space-y-6">
                        <div>
                            <h1 className="text-center text-2xl font-bold text-gray-900">
                                Verificação em duas etapas
                            </h1>
                            <p className="mt-2 text-center text-sm text-gray-600">
                                {useRecovery
                                    ? "Insira um dos códigos de recuperação que você guardou."
                                    : "Insira o código de 6 dígitos do seu app autenticador."}
                            </p>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-5">
                            {error && (
                                <div role="alert" className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                    {error}
                                </div>
                            )}

                            {useRecovery ? (
                                <FormField
                                    id="recovery-code"
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
                                    <label htmlFor="code" className="sr-only">
                                        Código
                                    </label>
                                    <InputOTP
                                        id="code"
                                        maxLength={6}
                                        value={code}
                                        onChange={setCode}
                                        autoComplete="one-time-code"
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
                                    onClick={() => navigate(ROUTES.SYSTEM.LOGIN, { replace: true })}
                                    className="flex-1 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                                >
                                    Voltar
                                </button>
                                <button
                                    type="submit"
                                    disabled={loading}
                                    className={cn(
                                        "flex-1 rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors",
                                        loading && "cursor-not-allowed opacity-70",
                                    )}
                                >
                                    {loading ? "Verificando..." : "Verificar"}
                                </button>
                            </div>

                            <button
                                type="button"
                                onClick={() => {
                                    setUseRecovery((v) => !v);
                                    setCode("");
                                    setRecoveryCode("");
                                    setError(null);
                                }}
                                className="w-full text-center text-sm font-medium text-primary-600 hover:text-primary-700"
                            >
                                {useRecovery
                                    ? "Usar aplicativo autenticador"
                                    : "Usar código de recuperação"}
                            </button>
                        </form>
                    </div>
                </div>
            </Layout>
        </>
    );
}
