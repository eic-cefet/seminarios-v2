import { useState } from "react";
import { Link, useLocation, useNavigate, useSearchParams } from "react-router-dom";
import { Layout } from "../components/Layout";
import { PageTitle } from "@shared/components/PageTitle";
import { FormField } from "@shared/components/FormField";
import { ROUTES } from "@shared/config/routes";
import { SocialLoginButtons } from "@shared/components/SocialLoginButtons";
import { buildUrl, cn, isSafeRedirect } from "@shared/lib/utils";
import { analytics } from "@shared/lib/analytics";
import { useAuth } from "@shared/contexts/AuthContext";
import { getErrorMessage } from "@shared/lib/errors";

export default function Login() {
    const navigate = useNavigate();
    const location = useLocation();
    const [searchParams] = useSearchParams();
    const { login } = useAuth();

    const raw =
        (location.state as { from?: string })?.from ||
        searchParams.get("redirect") ||
        "/";
    const redirectTo = isSafeRedirect(raw) ? raw : "/";
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
            const result = await login(formData.email, formData.password);
            if (result?.status === "two_factor_required") {
                navigate("/login/two-factor", {
                    state: {
                        challengeToken: result.challengeToken,
                        remember: result.remember,
                        from: redirectTo,
                    },
                });
                return;
            }
            analytics.event("login_email");
            navigate(redirectTo, { replace: true });
        } catch (err) {
            setError(getErrorMessage(err));
            analytics.event("login_failed", { method: "email" });
        } finally {
            setLoading(false);
        }
    };

    const handleSocialLogin = (provider: "google" | "github") => {
        analytics.event("login_social", { provider });
        if (redirectTo !== "/") {
            sessionStorage.setItem("auth_redirect", redirectTo);
        }
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
                                <div role="alert" className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                    {error}
                                </div>
                            )}

                            <FormField
                                id="email"
                                name="email"
                                type="email"
                                label="E-mail"
                                autoComplete="email"
                                required
                                value={formData.email}
                                onChange={handleChange}
                                placeholder="seu@email.com"
                            />

                            <div>
                                <div className="flex items-center justify-between mb-1">
                                    <span className="block text-sm font-medium text-gray-700" aria-hidden="true">
                                        Senha
                                    </span>
                                    <Link
                                        to={ROUTES.SYSTEM.FORGOT_PASSWORD}
                                        className="text-sm font-medium text-primary-600 hover:text-primary-700"
                                    >
                                        Esqueceu a senha?
                                    </Link>
                                </div>
                                <FormField
                                    id="password"
                                    name="password"
                                    type="password"
                                    label="Senha"
                                    labelClassName="sr-only"
                                    autoComplete="current-password"
                                    required
                                    value={formData.password}
                                    onChange={handleChange}
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
                                to={ROUTES.SYSTEM.REGISTER}
                                state={{ from: redirectTo !== "/" ? redirectTo : undefined }}
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
