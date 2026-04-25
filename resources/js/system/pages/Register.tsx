import { useState, useEffect, useRef } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import * as Label from "@radix-ui/react-label";
import { useQuery } from "@tanstack/react-query";
import ReCAPTCHA from "react-google-recaptcha";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Layout } from "../components/Layout";
import { PageTitle } from "@shared/components/PageTitle";
import { ROUTES } from "@shared/config/routes";
import { ReCaptcha, isRecaptchaEnabled } from "@shared/components/ReCaptcha";
import { SocialLoginButtons } from "@shared/components/SocialLoginButtons";
import { buildUrl, cn, isSafeRedirect } from "@shared/lib/utils";
import { useAuth } from "@shared/contexts/AuthContext";
import { getErrorMessage } from "@shared/lib/errors";
import { coursesApi } from "@shared/api/client";
import { analytics } from "@shared/lib/analytics";
import { FormField } from "@shared/components/FormField";
import { registerSchema, type RegisterFormValues } from "./Register.schema";

export default function Register() {
    const navigate = useNavigate();
    const location = useLocation();
    const { register: registerUser, isAuthenticated } = useAuth();

    const raw = (location.state as { from?: string })?.from || "/";
    const redirectTo = isSafeRedirect(raw) ? raw : "/";
    const recaptchaRef = useRef<ReCAPTCHA>(null);
    const [captchaToken, setCaptchaToken] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<RegisterFormValues>({
        resolver: zodResolver(registerSchema),
        defaultValues: {
            name: "",
            email: "",
            password: "",
            passwordConfirmation: "",
            courseSituation: "",
            courseRole: "",
            courseId: "",
            acceptedTerms: false,
            acceptedPrivacy: false,
        },
    });

    const { data: coursesData } = useQuery({
        queryKey: ["courses"],
        queryFn: () => coursesApi.list(),
    });

    const courses = coursesData?.data ?? [];

    useEffect(() => {
        if (isAuthenticated) {
            navigate(redirectTo, { replace: true });
        }
    }, [isAuthenticated, navigate, redirectTo]);

    const onSubmit = handleSubmit(async (values) => {
        setError(null);

        if (isRecaptchaEnabled() && !captchaToken) {
            setError("Por favor, complete o captcha");
            return;
        }

        try {
            await registerUser({
                name: values.name,
                email: values.email,
                password: values.password,
                passwordConfirmation: values.passwordConfirmation,
                courseSituation:
                    values.courseSituation as "studying" | "graduated",
                courseRole:
                    values.courseRole as "Aluno" | "Professor" | "Outro",
                courseId: values.courseId
                    ? parseInt(values.courseId)
                    : undefined,
                acceptedTerms: values.acceptedTerms,
                acceptedPrivacy: values.acceptedPrivacy,
            });
            analytics.event("register_account", {
                course_situation: values.courseSituation,
                course_role: values.courseRole,
            });
            navigate(redirectTo, { replace: true });
        } catch (err) {
            setError(getErrorMessage(err));
            recaptchaRef.current?.reset();
            setCaptchaToken(null);
        }
    });

    const handleSocialLogin = (provider: "google" | "github") => {
        analytics.event("register_social", { provider });
        if (redirectTo !== "/") {
            sessionStorage.setItem("auth_redirect", redirectTo);
        }
        window.location.href = buildUrl(`/auth/${provider}`);
    };

    const courseSelectionError =
        errors.courseSituation?.message ?? errors.courseRole?.message;
    const consentError =
        errors.acceptedTerms?.message ?? errors.acceptedPrivacy?.message;

    const nameField = register("name");
    const emailField = register("email");
    const passwordField = register("password");
    const passwordConfirmationField = register("passwordConfirmation");
    const courseSituationField = register("courseSituation");
    const courseRoleField = register("courseRole");
    const courseIdField = register("courseId");
    const acceptedTermsField = register("acceptedTerms");
    const acceptedPrivacyField = register("acceptedPrivacy");

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

                        <SocialLoginButtons
                            onSocialLogin={handleSocialLogin}
                            actionLabel="Cadastrar"
                            dividerText="ou cadastre-se com e-mail"
                            dividerBgColor="bg-gray-50"
                        />

                        <form onSubmit={onSubmit} className="space-y-5" noValidate>
                            {error && (
                                <div role="alert" className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                    {error}
                                </div>
                            )}

                            {courseSelectionError && (
                                <div role="alert" className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                    {courseSelectionError}
                                </div>
                            )}

                            {consentError && (
                                <div role="alert" className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                    {consentError}
                                </div>
                            )}

                            <FormField
                                id="name"
                                label="Nome completo"
                                type="text"
                                required
                                autoComplete="name"
                                placeholder="Seu nome completo"
                                error={errors.name?.message}
                                {...nameField}
                            />

                            <FormField
                                id="email"
                                label="E-mail"
                                type="email"
                                required
                                autoComplete="email"
                                placeholder="seu@email.com"
                                error={errors.email?.message}
                                {...emailField}
                            />

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
                                        required
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                        {...courseSituationField}
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
                                        required
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                        {...courseRoleField}
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
                                    className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                    {...courseIdField}
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

                            <FormField
                                id="password"
                                label="Senha"
                                type="password"
                                required
                                autoComplete="new-password"
                                placeholder="Mínimo de 8 caracteres"
                                hint="Mínimo de 8 caracteres"
                                error={errors.password?.message}
                                {...passwordField}
                            />

                            <FormField
                                id="passwordConfirmation"
                                label="Confirmar senha"
                                type="password"
                                required
                                autoComplete="new-password"
                                placeholder="Digite a senha novamente"
                                error={errors.passwordConfirmation?.message}
                                {...passwordConfirmationField}
                            />

                            <ReCaptcha
                                ref={recaptchaRef}
                                onVerify={setCaptchaToken}
                                onExpire={() => setCaptchaToken(null)}
                            />

                            <div className="space-y-2 text-sm text-gray-700">
                                <label className="flex items-start gap-2">
                                    <input
                                        type="checkbox"
                                        className="mt-1"
                                        {...acceptedTermsField}
                                    />
                                    <span>
                                        Li e aceito os{" "}
                                        <Link
                                            to={ROUTES.SYSTEM.TERMS}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-primary-600 underline"
                                        >
                                            Termos de Uso
                                        </Link>
                                        .
                                    </span>
                                </label>
                                <label className="flex items-start gap-2">
                                    <input
                                        type="checkbox"
                                        className="mt-1"
                                        {...acceptedPrivacyField}
                                    />
                                    <span>
                                        Li e aceito a{" "}
                                        <Link
                                            to={ROUTES.SYSTEM.PRIVACY_POLICY}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-primary-600 underline"
                                        >
                                            Política de Privacidade
                                        </Link>
                                        .
                                    </span>
                                </label>
                            </div>

                            <button
                                type="submit"
                                disabled={
                                    isSubmitting ||
                                    (isRecaptchaEnabled() && !captchaToken)
                                }
                                className={cn(
                                    "w-full rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors",
                                    (isSubmitting ||
                                        (isRecaptchaEnabled() &&
                                            !captchaToken)) &&
                                        "opacity-70 cursor-not-allowed",
                                )}
                            >
                                {isSubmitting ? "Criando conta..." : "Criar conta"}
                            </button>
                        </form>

                        <p className="text-center text-sm text-gray-500">
                            Já tem uma conta?{" "}
                            <Link
                                to={ROUTES.SYSTEM.LOGIN}
                                state={{ from: redirectTo !== "/" ? redirectTo : undefined }}
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
