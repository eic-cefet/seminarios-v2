import { useState, useEffect, useRef } from "react";
import { Link, useNavigate } from "react-router-dom";
import * as Label from "@radix-ui/react-label";
import { useQuery } from "@tanstack/react-query";
import ReCAPTCHA from "react-google-recaptcha";
import { Layout } from "../components/Layout";
import { PageTitle } from "@shared/components/PageTitle";
import { ReCaptcha, isRecaptchaEnabled } from "@shared/components/ReCaptcha";
import { GoogleIcon, GithubIcon } from "@shared/components/icons/SocialIcons";
import { buildUrl, cn } from "@shared/lib/utils";
import { useAuth } from "@shared/contexts/AuthContext";
import { getErrorMessage } from "@shared/lib/errors";
import { coursesApi } from "@shared/api/client";
import { analytics } from "@shared/lib/analytics";

export default function Register() {
    const navigate = useNavigate();
    const { register, isAuthenticated } = useAuth();
    const recaptchaRef = useRef<ReCAPTCHA>(null);
    const [captchaToken, setCaptchaToken] = useState<string | null>(null);
    const [formData, setFormData] = useState({
        name: "",
        email: "",
        password: "",
        passwordConfirmation: "",
        courseSituation: "" as "" | "studying" | "graduated",
        courseRole: "" as "" | "Aluno" | "Professor" | "Outro",
        courseId: "",
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const { data: coursesData } = useQuery({
        queryKey: ["courses"],
        queryFn: () => coursesApi.list(),
    });

    const courses = coursesData?.data ?? [];

    // Redirect if already authenticated
    useEffect(() => {
        if (isAuthenticated) {
            navigate("/", { replace: true });
        }
    }, [isAuthenticated, navigate]);

    const handleChange = (
        e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>,
    ) => {
        setFormData((prev) => ({
            ...prev,
            [e.target.name]: e.target.value,
        }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);

        if (isRecaptchaEnabled() && !captchaToken) {
            setError("Por favor, complete o captcha");
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

        if (!formData.courseSituation || !formData.courseRole) {
            setError("Preencha todos os campos obrigatórios");
            return;
        }

        setLoading(true);

        try {
            await register({
                name: formData.name,
                email: formData.email,
                password: formData.password,
                passwordConfirmation: formData.passwordConfirmation,
                courseSituation: formData.courseSituation,
                courseRole: formData.courseRole,
                courseId: formData.courseId
                    ? parseInt(formData.courseId)
                    : undefined,
            });
            analytics.event("register_account", {
                course_situation: formData.courseSituation,
                course_role: formData.courseRole,
            });
            navigate("/");
        } catch (err) {
            setError(getErrorMessage(err));
            recaptchaRef.current?.reset();
            setCaptchaToken(null);
        } finally {
            setLoading(false);
        }
    };

    const handleSocialLogin = (provider: "google" | "github") => {
        analytics.event("register_social", { provider });
        window.location.href = buildUrl(`/auth/${provider}`);
    };

    return (
        <>
            <PageTitle title="Cadastro" />
            <Layout>
                <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                    <div className="w-full max-w-md space-y-8">
                        <div>
                            <h2 className="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
                                Criar conta
                            </h2>
                            <p className="mt-2 text-center text-sm text-gray-600">
                                Cadastre-se para participar dos seminários
                            </p>
                        </div>

                        {/* Social Registration */}
                        <div className="space-y-3">
                            <button
                                type="button"
                                onClick={() => handleSocialLogin("google")}
                                className="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                            >
                                <GoogleIcon className="h-5 w-5" />
                                Cadastrar com Google
                            </button>
                            <button
                                type="button"
                                onClick={() => handleSocialLogin("github")}
                                className="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                            >
                                <GithubIcon className="h-5 w-5" />
                                Cadastrar com GitHub
                            </button>
                        </div>

                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-gray-200" />
                            </div>
                            <div className="relative flex justify-center text-sm">
                                <span className="bg-gray-50 px-2 text-gray-500">
                                    ou cadastre-se com e-mail
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
                                    htmlFor="name"
                                    className="block text-sm font-medium text-gray-700"
                                >
                                    Nome completo
                                </Label.Root>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    required
                                    value={formData.name}
                                    onChange={handleChange}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    placeholder="Seu nome completo"
                                />
                            </div>

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

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label.Root
                                        htmlFor="courseSituation"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        Situação
                                    </Label.Root>
                                    <select
                                        id="courseSituation"
                                        name="courseSituation"
                                        required
                                        value={formData.courseSituation}
                                        onChange={handleChange}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    >
                                        <option value="">Selecione...</option>
                                        <option value="studying">
                                            Cursando
                                        </option>
                                        <option value="graduated">
                                            Formado
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <Label.Root
                                        htmlFor="courseRole"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        Vínculo
                                    </Label.Root>
                                    <select
                                        id="courseRole"
                                        name="courseRole"
                                        required
                                        value={formData.courseRole}
                                        onChange={handleChange}
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    >
                                        <option value="">Selecione...</option>
                                        <option value="Aluno">Aluno</option>
                                        <option value="Professor">
                                            Professor
                                        </option>
                                        <option value="Outro">Outro</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <Label.Root
                                    htmlFor="courseId"
                                    className="block text-sm font-medium text-gray-700"
                                >
                                    Curso
                                </Label.Root>
                                <select
                                    id="courseId"
                                    name="courseId"
                                    value={formData.courseId}
                                    onChange={handleChange}
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                >
                                    <option value="">
                                        Selecione um curso (opcional)
                                    </option>
                                    {courses.map((course) => (
                                        <option
                                            key={course.id}
                                            value={course.id}
                                        >
                                            {course.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <Label.Root
                                    htmlFor="password"
                                    className="block text-sm font-medium text-gray-700"
                                >
                                    Senha
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
                                    Confirmar senha
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

                            <ReCaptcha
                                ref={recaptchaRef}
                                onVerify={setCaptchaToken}
                                onExpire={() => setCaptchaToken(null)}
                            />

                            <button
                                type="submit"
                                disabled={
                                    loading ||
                                    (isRecaptchaEnabled() && !captchaToken)
                                }
                                className={cn(
                                    "w-full rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors",
                                    (loading ||
                                        (isRecaptchaEnabled() &&
                                            !captchaToken)) &&
                                        "opacity-70 cursor-not-allowed",
                                )}
                            >
                                {loading ? "Criando conta..." : "Criar conta"}
                            </button>
                        </form>

                        <p className="text-center text-sm text-gray-500">
                            Já tem uma conta?{" "}
                            <Link
                                to="/login"
                                className="font-medium text-primary-600 hover:text-primary-700"
                            >
                                Entrar
                            </Link>
                        </p>
                    </div>
                </div>
            </Layout>
        </>
    );
}

