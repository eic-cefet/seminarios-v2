import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import * as Label from "@radix-ui/react-label";
import { Layout } from "../components/Layout";
import { PageTitle } from "@shared/components/PageTitle";
import { SocialLoginButtons } from "@shared/components/SocialLoginButtons";
import { buildUrl, cn } from "@shared/lib/utils";
import { analytics } from "@shared/lib/analytics";
import { useAuth } from "@shared/contexts/AuthContext";
import { getErrorMessage } from "@shared/lib/errors";

export default function Login() {
    const navigate = useNavigate();
    const { login } = useAuth();
    const [formData, setFormData] = useState({
        email: "",
        password: "",
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setFormData((prev) => ({
            ...prev,
            [e.target.name]: e.target.value,
        }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setLoading(true);

        try {
            await login(formData.email, formData.password);
            analytics.event("login_email");
            navigate("/");
        } catch (err) {
            setError(getErrorMessage(err));
            analytics.event("login_failed", { method: "email" });
        } finally {
            setLoading(false);
        }
    };

    const handleSocialLogin = (provider: "google" | "github") => {
        analytics.event("login_social", { provider });
        window.location.href = buildUrl(`/auth/${provider}`);
    };

    return (
        <>
            <PageTitle title="Login" />
            <Layout>
                <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                    <div className="w-full max-w-md space-y-8">
                        <div>
                            <h2 className="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
                                Entrar
                            </h2>
                            <p className="mt-2 text-center text-sm text-gray-600">
                                Acesse sua conta para participar dos seminarios
                            </p>
                        </div>

                        <SocialLoginButtons
                            onSocialLogin={handleSocialLogin}
                            actionLabel="Entrar"
                            dividerText="ou entre com e-mail"
                            dividerBgColor="bg-gray-50"
                        />

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
                                    value={formData.email}
                                    onChange={handleChange}
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
                                    <Link
                                        to="/recuperar-senha"
                                        className="text-sm font-medium text-primary-600 hover:text-primary-700"
                                    >
                                        Esqueceu a senha?
                                    </Link>
                                </div>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    value={formData.password}
                                    onChange={handleChange}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    placeholder="Sua senha"
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
                                {loading ? "Entrando..." : "Entrar"}
                            </button>
                        </form>

                        <p className="text-center text-sm text-gray-500">
                            Nao tem uma conta?{" "}
                            <Link
                                to="/cadastro"
                                className="font-medium text-primary-600 hover:text-primary-700"
                            >
                                Criar conta
                            </Link>
                        </p>
                    </div>
                </div>
            </Layout>
        </>
    );
}
