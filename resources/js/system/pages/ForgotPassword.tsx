import { useState } from "react";
import { Link } from "react-router-dom";
import * as Label from "@radix-ui/react-label";
import { Mail, ArrowLeft, CheckCircle } from "lucide-react";
import { Layout } from "../components/Layout";
import { PageTitle } from "@shared/components/PageTitle";
import { cn } from "@shared/lib/utils";
import { authApi } from "@shared/api/client";
import { getErrorMessage } from "@shared/lib/errors";

export default function ForgotPassword() {
    const [email, setEmail] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setLoading(true);

        try {
            await authApi.forgotPassword(email);
            setSuccess(true);
        } catch (err) {
            setError(getErrorMessage(err));
        } finally {
            setLoading(false);
        }
    };

    if (success) {
        return (
            <>
                <PageTitle title="E-mail Enviado" />
                <Layout>
                    <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                        <div className="w-full max-w-md text-center space-y-6">
                            <div className="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                <CheckCircle className="h-8 w-8 text-green-600" />
                            </div>
                            <h2 className="text-2xl font-bold text-gray-900">
                                E-mail enviado!
                            </h2>
                            <p className="text-gray-600">
                                Se existe uma conta com o e-mail{" "}
                                <span className="font-medium">{email}</span>,
                                você receberá um link para redefinir sua senha.
                            </p>
                            <p className="text-sm text-gray-500">
                                Verifique sua caixa de entrada e a pasta de spam.
                            </p>
                            <div className="pt-4">
                                <Link
                                    to="/login"
                                    className="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700"
                                >
                                    <ArrowLeft className="h-4 w-4" />
                                    Voltar para o login
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
            <PageTitle title="Recuperar Senha" />
            <Layout>
                <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                    <div className="w-full max-w-md space-y-8">
                        <div className="text-center">
                            <div className="mx-auto w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center mb-4">
                                <Mail className="h-6 w-6 text-primary-600" />
                            </div>
                            <h2 className="text-3xl font-bold tracking-tight text-gray-900">
                                Recuperar senha
                            </h2>
                            <p className="mt-2 text-sm text-gray-600">
                                Digite seu e-mail e enviaremos um link para
                                redefinir sua senha.
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
                                    htmlFor="email"
                                    className="block text-sm font-medium text-gray-700"
                                >
                                    E-mail
                                </Label.Root>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    placeholder="seu@email.com"
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
                                {loading ? "Enviando..." : "Enviar link de recuperação"}
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
