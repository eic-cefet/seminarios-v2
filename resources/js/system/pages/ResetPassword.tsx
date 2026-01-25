import { useState, useEffect } from "react";
import { Link, useSearchParams, useNavigate } from "react-router-dom";
import * as Label from "@radix-ui/react-label";
import { KeyRound, ArrowLeft, CheckCircle, AlertCircle } from "lucide-react";
import { Layout } from "../components/Layout";
import { PageTitle } from "@shared/components/PageTitle";
import { cn } from "@shared/lib/utils";
import { authApi } from "@shared/api/client";
import { getErrorMessage } from "@shared/lib/errors";
import { analytics } from "@shared/lib/analytics";

export default function ResetPassword() {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();

    const token = searchParams.get("token");
    const email = searchParams.get("email");

    const [formData, setFormData] = useState({
        password: "",
        passwordConfirmation: "",
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState(false);

    // Validate required params
    const hasRequiredParams = token && email;

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setFormData((prev) => ({
            ...prev,
            [e.target.name]: e.target.value,
        }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);

        if (!token || !email) {
            setError("Link de redefinição inválido");
            return;
        }

        if (formData.password !== formData.passwordConfirmation) {
            setError("As senhas não coincidem");
            return;
        }

        if (formData.password.length < 8) {
            setError("A senha deve ter pelo menos 8 caracteres");
            return;
        }

        setLoading(true);

        try {
            await authApi.resetPassword({
                token,
                email,
                password: formData.password,
                password_confirmation: formData.passwordConfirmation,
            });
            analytics.event("reset_password");
            setSuccess(true);
        } catch (err) {
            setError(getErrorMessage(err));
        } finally {
            setLoading(false);
        }
    };

    // Redirect to login after success
    useEffect(() => {
        if (success) {
            const timer = setTimeout(() => {
                navigate("/login");
            }, 3000);
            return () => clearTimeout(timer);
        }
    }, [success, navigate]);

    if (!hasRequiredParams) {
        return (
            <>
                <PageTitle title="Link Inválido" />
                <Layout>
                    <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                        <div className="w-full max-w-md text-center space-y-6">
                            <div className="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                                <AlertCircle className="h-8 w-8 text-red-600" />
                            </div>
                            <h2 className="text-2xl font-bold text-gray-900">
                                Link inválido
                            </h2>
                            <p className="text-gray-600">
                                O link de redefinição de senha é inválido ou
                                expirou. Por favor, solicite um novo link.
                            </p>
                            <div className="pt-4">
                                <Link
                                    to="/recuperar-senha"
                                    className="inline-flex items-center justify-center gap-2 rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700"
                                >
                                    Solicitar novo link
                                </Link>
                            </div>
                        </div>
                    </div>
                </Layout>
            </>
        );
    }

    if (success) {
        return (
            <>
                <PageTitle title="Senha Redefinida" />
                <Layout>
                    <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                        <div className="w-full max-w-md text-center space-y-6">
                            <div className="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                <CheckCircle className="h-8 w-8 text-green-600" />
                            </div>
                            <h2 className="text-2xl font-bold text-gray-900">
                                Senha redefinida!
                            </h2>
                            <p className="text-gray-600">
                                Sua senha foi alterada com sucesso. Você será
                                redirecionado para a página de login.
                            </p>
                            <div className="pt-4">
                                <Link
                                    to="/login"
                                    className="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700"
                                >
                                    <ArrowLeft className="h-4 w-4" />
                                    Ir para o login
                                </Link>
                            </div>
                        </div>
                    </div>
                </Layout>
            </>
        );
    }

    return (
        <>
            <PageTitle title="Redefinir Senha" />
            <Layout>
                <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                    <div className="w-full max-w-md space-y-8">
                        <div className="text-center">
                            <div className="mx-auto w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center mb-4">
                                <KeyRound className="h-6 w-6 text-primary-600" />
                            </div>
                            <h2 className="text-3xl font-bold tracking-tight text-gray-900">
                                Redefinir senha
                            </h2>
                            <p className="mt-2 text-sm text-gray-600">
                                Digite sua nova senha abaixo.
                            </p>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-5">
                            {error && (
                                <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                    {error}
                                </div>
                            )}

                            <div>
                                <Label.Root
                                    htmlFor="password"
                                    className="block text-sm font-medium text-gray-700"
                                >
                                    Nova senha
                                </Label.Root>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    value={formData.password}
                                    onChange={handleChange}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    placeholder="Mínimo de 8 caracteres"
                                />
                            </div>

                            <div>
                                <Label.Root
                                    htmlFor="passwordConfirmation"
                                    className="block text-sm font-medium text-gray-700"
                                >
                                    Confirmar nova senha
                                </Label.Root>
                                <input
                                    id="passwordConfirmation"
                                    name="passwordConfirmation"
                                    type="password"
                                    required
                                    value={formData.passwordConfirmation}
                                    onChange={handleChange}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    placeholder="Digite a senha novamente"
                                />
                            </div>

                            <button
                                type="submit"
                                disabled={loading}
                                className={cn(
                                    "w-full rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors",
                                    loading && "opacity-70 cursor-not-allowed",
                                )}
                            >
                                {loading ? "Redefinindo..." : "Redefinir senha"}
                            </button>
                        </form>

                        <p className="text-center text-sm text-gray-500">
                            <Link
                                to="/login"
                                className="inline-flex items-center gap-2 font-medium text-primary-600 hover:text-primary-700"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Voltar para o login
                            </Link>
                        </p>
                    </div>
                </div>
            </Layout>
        </>
    );
}
