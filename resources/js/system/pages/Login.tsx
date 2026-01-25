import { useState } from "react";
import { Link } from "react-router-dom";
import * as Label from "@radix-ui/react-label";
import { Layout } from "../components/Layout";
import { PageTitle } from "@shared/components/PageTitle";
import { GoogleIcon, GithubIcon } from "@shared/components/icons/SocialIcons";
import { buildUrl, cn } from "@shared/lib/utils";

export default function Login() {
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
            // TODO: Implement actual login
            console.log("Login:", formData);
            await new Promise((resolve) => setTimeout(resolve, 1000));
            // Redirect to home after success
        } catch {
            setError("E-mail ou senha incorretos");
        } finally {
            setLoading(false);
        }
    };

    const handleSocialLogin = (provider: "google" | "github") => {
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

                        {/* Social Login */}
                        <div className="space-y-3">
                            <button
                                type="button"
                                onClick={() => handleSocialLogin("google")}
                                className="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                            >
                                <GoogleIcon className="h-5 w-5" />
                                Entrar com Google
                            </button>
                            <button
                                type="button"
                                onClick={() => handleSocialLogin("github")}
                                className="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                            >
                                <GithubIcon className="h-5 w-5" />
                                Entrar com GitHub
                            </button>
                        </div>

                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-gray-200" />
                            </div>
                            <div className="relative flex justify-center text-sm">
                                <span className="bg-gray-50 px-2 text-gray-500">
                                    ou entre com e-mail
                                </span>
                            </div>
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
