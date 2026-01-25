import { coursesApi, profileApi } from "@shared/api/client";
import { PageTitle } from "@shared/components/PageTitle";
import { useAuth } from "@shared/contexts/AuthContext";
import { getErrorMessage, getFieldErrors } from "@shared/lib/errors";
import { cn, formatDateTime } from "@shared/lib/utils";
import { analytics } from "@shared/lib/analytics";
import type { User as UserType } from "@shared/types";
import { useMutation, useQuery } from "@tanstack/react-query";
import {
    Calendar,
    Check,
    ChevronLeft,
    ChevronRight,
    Download,
    FileText,
    GraduationCap,
    Loader2,
    Lock,
    Mail,
    MapPin,
    User,
    X,
} from "lucide-react";
import { useState } from "react";
import { Link, Navigate } from "react-router-dom";
import { Badge } from "../components/Badge";
import { Layout } from "../components/Layout";

export default function Profile() {
    const { user, isLoading: authLoading, refreshUser } = useAuth();

    // Redirect if not authenticated
    if (!authLoading && !user) {
        return <Navigate to="/login" replace />;
    }

    if (authLoading) {
        return (
            <Layout>
                <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center">
                    <Loader2 className="h-8 w-8 animate-spin text-primary-600" />
                </div>
            </Layout>
        );
    }

    return (
        <>
            <PageTitle title="Perfil" />
            <Layout>
                <div className="bg-white border-b border-gray-200">
                    <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                        <h1 className="text-2xl font-bold text-gray-900">
                            Meu Perfil
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Gerencie suas informações pessoais e configurações
                        </p>
                    </div>
                </div>

                <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">
                    <ProfileInfoSection user={user!} onUpdate={refreshUser} />
                    <StudentDataSection user={user!} onUpdate={refreshUser} />
                    <PasswordSection />
                    <RegistrationsSection />
                    <CertificatesSection />
                </div>
            </Layout>
        </>
    );
}

interface ProfileInfoSectionProps {
    user: { id: number; name: string; email: string };
    onUpdate: () => Promise<void>;
}

function ProfileInfoSection({ user, onUpdate }: ProfileInfoSectionProps) {
    const [isEditing, setIsEditing] = useState(false);
    const [name, setName] = useState(user.name);
    const [email, setEmail] = useState(user.email);
    const [error, setError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [success, setSuccess] = useState(false);

    const mutation = useMutation({
        mutationFn: () => profileApi.update({ name, email }),
        onSuccess: async () => {
            setError(null);
            setFieldErrors({});
            setSuccess(true);
            setIsEditing(false);
            analytics.event("profile_info_update");
            await onUpdate();
            setTimeout(() => setSuccess(false), 3000);
        },
        onError: (err) => {
            setError(getErrorMessage(err));
            setFieldErrors(getFieldErrors(err) || {});
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate();
    };

    const handleCancel = () => {
        setName(user.name);
        setEmail(user.email);
        setError(null);
        setFieldErrors({});
        setIsEditing(false);
    };

    return (
        <section className="rounded-lg border border-gray-200 bg-white">
            <div className="border-b border-gray-200 px-6 py-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <User className="h-5 w-5 text-gray-400" />
                        <h2 className="text-lg font-semibold text-gray-900">
                            Informações pessoais
                        </h2>
                    </div>
                    {!isEditing && (
                        <button
                            onClick={() => setIsEditing(true)}
                            className="text-sm font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                        >
                            Editar
                        </button>
                    )}
                </div>
            </div>

            <div className="px-6 py-4">
                {success && (
                    <div className="mb-4 flex items-center gap-2 rounded-md bg-green-50 p-3 text-sm text-green-700">
                        <Check className="h-4 w-4" />
                        Perfil atualizado com sucesso!
                    </div>
                )}

                {isEditing ? (
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {error && (
                            <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                {error}
                            </div>
                        )}

                        <div>
                            <label
                                htmlFor="name"
                                className="block text-sm font-medium text-gray-700"
                            >
                                Nome
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                required
                                className={cn(
                                    "mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1",
                                    fieldErrors.name
                                        ? "border-red-300 focus:border-red-500 focus:ring-red-500"
                                        : "border-gray-300 focus:border-primary-500 focus:ring-primary-500",
                                )}
                            />
                            {fieldErrors.name && (
                                <p className="mt-1 text-sm text-red-600">
                                    {fieldErrors.name}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="email"
                                className="block text-sm font-medium text-gray-700"
                            >
                                E-mail
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                required
                                className={cn(
                                    "mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1",
                                    fieldErrors.email
                                        ? "border-red-300 focus:border-red-500 focus:ring-red-500"
                                        : "border-gray-300 focus:border-primary-500 focus:ring-primary-500",
                                )}
                            />
                            {fieldErrors.email && (
                                <p className="mt-1 text-sm text-red-600">
                                    {fieldErrors.email}
                                </p>
                            )}
                        </div>

                        <div className="flex gap-3 pt-2">
                            <button
                                type="button"
                                onClick={handleCancel}
                                className="flex-1 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors cursor-pointer"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                disabled={mutation.isPending}
                                className={cn(
                                    "flex-1 rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer",
                                    mutation.isPending &&
                                        "opacity-70 cursor-not-allowed",
                                )}
                            >
                                {mutation.isPending ? "Salvando..." : "Salvar"}
                            </button>
                        </div>
                    </form>
                ) : (
                    <dl className="space-y-4">
                        <div className="flex items-center gap-3">
                            <dt className="flex items-center gap-2 text-sm text-gray-500 w-24">
                                <User className="h-4 w-4" />
                                Nome
                            </dt>
                            <dd className="text-sm text-gray-900">
                                {user.name}
                            </dd>
                        </div>
                        <div className="flex items-center gap-3">
                            <dt className="flex items-center gap-2 text-sm text-gray-500 w-24">
                                <Mail className="h-4 w-4" />
                                E-mail
                            </dt>
                            <dd className="text-sm text-gray-900">
                                {user.email}
                            </dd>
                        </div>
                    </dl>
                )}
            </div>
        </section>
    );
}

interface StudentDataSectionProps {
    user: UserType;
    onUpdate: () => Promise<void>;
}

function StudentDataSection({ user, onUpdate }: StudentDataSectionProps) {
    const [isEditing, setIsEditing] = useState(false);
    const [courseId, setCourseId] = useState(
        user.student_data?.course?.id?.toString() || "",
    );
    const [courseSituation, setCourseSituation] = useState<
        "studying" | "graduated"
    >(user.student_data?.course_situation || "studying");
    const [courseRole, setCourseRole] = useState<
        "Aluno" | "Professor" | "Outro"
    >(user.student_data?.course_role || "Aluno");
    const [error, setError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [success, setSuccess] = useState(false);

    const { data: coursesData } = useQuery({
        queryKey: ["courses"],
        queryFn: () => coursesApi.list(),
    });

    const courses = coursesData?.data ?? [];

    const mutation = useMutation({
        mutationFn: () =>
            profileApi.updateStudentData({
                course_id: courseId ? parseInt(courseId) : null,
                course_situation: courseSituation,
                course_role: courseRole,
            }),
        onSuccess: async () => {
            setError(null);
            setFieldErrors({});
            setSuccess(true);
            setIsEditing(false);
            analytics.event("profile_student_data_update");
            await onUpdate();
            setTimeout(() => setSuccess(false), 3000);
        },
        onError: (err) => {
            setError(getErrorMessage(err));
            setFieldErrors(getFieldErrors(err) || {});
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate();
    };

    const handleCancel = () => {
        setCourseId(user.student_data?.course?.id?.toString() || "");
        setCourseSituation(user.student_data?.course_situation || "studying");
        setCourseRole(user.student_data?.course_role || "Aluno");
        setError(null);
        setFieldErrors({});
        setIsEditing(false);
    };

    return (
        <section className="rounded-lg border border-gray-200 bg-white">
            <div className="border-b border-gray-200 px-6 py-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <GraduationCap className="h-5 w-5 text-gray-400" />
                        <h2 className="text-lg font-semibold text-gray-900">
                            Dados acadêmicos
                        </h2>
                    </div>
                    {!isEditing && (
                        <button
                            onClick={() => setIsEditing(true)}
                            className="text-sm font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                        >
                            Editar
                        </button>
                    )}
                </div>
            </div>

            <div className="px-6 py-4">
                {success && (
                    <div className="mb-4 flex items-center gap-2 rounded-md bg-green-50 p-3 text-sm text-green-700">
                        <Check className="h-4 w-4" />
                        Dados acadêmicos atualizados com sucesso!
                    </div>
                )}

                {isEditing ? (
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {error && (
                            <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                {error}
                            </div>
                        )}

                        <div>
                            <label
                                htmlFor="courseId"
                                className="block text-sm font-medium text-gray-700"
                            >
                                Curso
                            </label>
                            <select
                                id="courseId"
                                value={courseId}
                                onChange={(e) => setCourseId(e.target.value)}
                                className={cn(
                                    "mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1",
                                    fieldErrors.course_id
                                        ? "border-red-300 focus:border-red-500 focus:ring-red-500"
                                        : "border-gray-300 focus:border-primary-500 focus:ring-primary-500",
                                )}
                            >
                                <option value="">
                                    Selecione um curso (opcional)
                                </option>
                                {courses.map((course) => (
                                    <option key={course.id} value={course.id}>
                                        {course.name}
                                    </option>
                                ))}
                            </select>
                            {fieldErrors.course_id && (
                                <p className="mt-1 text-sm text-red-600">
                                    {fieldErrors.course_id}
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    htmlFor="courseSituation"
                                    className="block text-sm font-medium text-gray-700"
                                >
                                    Situação
                                </label>
                                <select
                                    id="courseSituation"
                                    value={courseSituation}
                                    onChange={(e) =>
                                        setCourseSituation(
                                            e.target.value as
                                                | "studying"
                                                | "graduated",
                                        )
                                    }
                                    required
                                    className={cn(
                                        "mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1",
                                        fieldErrors.course_situation
                                            ? "border-red-300 focus:border-red-500 focus:ring-red-500"
                                            : "border-gray-300 focus:border-primary-500 focus:ring-primary-500",
                                    )}
                                >
                                    <option value="studying">Cursando</option>
                                    <option value="graduated">Formado</option>
                                </select>
                                {fieldErrors.course_situation && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {fieldErrors.course_situation}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="courseRole"
                                    className="block text-sm font-medium text-gray-700"
                                >
                                    Vínculo
                                </label>
                                <select
                                    id="courseRole"
                                    value={courseRole}
                                    onChange={(e) =>
                                        setCourseRole(
                                            e.target.value as
                                                | "Aluno"
                                                | "Professor"
                                                | "Outro",
                                        )
                                    }
                                    required
                                    className={cn(
                                        "mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1",
                                        fieldErrors.course_role
                                            ? "border-red-300 focus:border-red-500 focus:ring-red-500"
                                            : "border-gray-300 focus:border-primary-500 focus:ring-primary-500",
                                    )}
                                >
                                    <option value="Aluno">Aluno</option>
                                    <option value="Professor">Professor</option>
                                    <option value="Outro">Outro</option>
                                </select>
                                {fieldErrors.course_role && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {fieldErrors.course_role}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="flex gap-3 pt-2">
                            <button
                                type="button"
                                onClick={handleCancel}
                                className="flex-1 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors cursor-pointer"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                disabled={mutation.isPending}
                                className={cn(
                                    "flex-1 rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer",
                                    mutation.isPending &&
                                        "opacity-70 cursor-not-allowed",
                                )}
                            >
                                {mutation.isPending ? "Salvando..." : "Salvar"}
                            </button>
                        </div>
                    </form>
                ) : (
                    <dl className="space-y-4">
                        <div className="flex items-center gap-3">
                            <dt className="flex items-center gap-2 text-sm text-gray-500 w-24">
                                <GraduationCap className="h-4 w-4" />
                                Curso
                            </dt>
                            <dd className="text-sm text-gray-900">
                                {user.student_data?.course?.name ||
                                    "Não informado"}
                            </dd>
                        </div>
                        <div className="flex items-center gap-3">
                            <dt className="text-sm text-gray-500 w-24">
                                Situação
                            </dt>
                            <dd className="text-sm text-gray-900">
                                {user.student_data?.course_situation ===
                                "studying"
                                    ? "Cursando"
                                    : "Formado"}
                            </dd>
                        </div>
                        <div className="flex items-center gap-3">
                            <dt className="text-sm text-gray-500 w-24">
                                Vínculo
                            </dt>
                            <dd className="text-sm text-gray-900">
                                {user.student_data?.course_role ||
                                    "Não informado"}
                            </dd>
                        </div>
                    </dl>
                )}
            </div>
        </section>
    );
}

function PasswordSection() {
    const [isEditing, setIsEditing] = useState(false);
    const [currentPassword, setCurrentPassword] = useState("");
    const [password, setPassword] = useState("");
    const [passwordConfirmation, setPasswordConfirmation] = useState("");
    const [error, setError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [success, setSuccess] = useState(false);

    const mutation = useMutation({
        mutationFn: () =>
            profileApi.updatePassword({
                current_password: currentPassword,
                password,
                password_confirmation: passwordConfirmation,
            }),
        onSuccess: () => {
            setError(null);
            setFieldErrors({});
            setSuccess(true);
            setIsEditing(false);
            setCurrentPassword("");
            setPassword("");
            setPasswordConfirmation("");
            analytics.event("profile_password_change");
            setTimeout(() => setSuccess(false), 3000);
        },
        onError: (err) => {
            setError(getErrorMessage(err));
            setFieldErrors(getFieldErrors(err) || {});
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate();
    };

    const handleCancel = () => {
        setCurrentPassword("");
        setPassword("");
        setPasswordConfirmation("");
        setError(null);
        setFieldErrors({});
        setIsEditing(false);
    };

    return (
        <section className="rounded-lg border border-gray-200 bg-white">
            <div className="border-b border-gray-200 px-6 py-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Lock className="h-5 w-5 text-gray-400" />
                        <h2 className="text-lg font-semibold text-gray-900">
                            Senha
                        </h2>
                    </div>
                    {!isEditing && (
                        <button
                            onClick={() => setIsEditing(true)}
                            className="text-sm font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                        >
                            Alterar senha
                        </button>
                    )}
                </div>
            </div>

            <div className="px-6 py-4">
                {success && (
                    <div className="flex items-center gap-2 rounded-md bg-green-50 p-3 text-sm text-green-700">
                        <Check className="h-4 w-4" />
                        Senha atualizada com sucesso!
                    </div>
                )}

                {isEditing ? (
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {error && (
                            <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                {error}
                            </div>
                        )}

                        <div>
                            <label
                                htmlFor="current-password"
                                className="block text-sm font-medium text-gray-700"
                            >
                                Senha atual
                            </label>
                            <input
                                id="current-password"
                                type="password"
                                value={currentPassword}
                                onChange={(e) =>
                                    setCurrentPassword(e.target.value)
                                }
                                required
                                className={cn(
                                    "mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1",
                                    fieldErrors.current_password
                                        ? "border-red-300 focus:border-red-500 focus:ring-red-500"
                                        : "border-gray-300 focus:border-primary-500 focus:ring-primary-500",
                                )}
                            />
                            {fieldErrors.current_password && (
                                <p className="mt-1 text-sm text-red-600">
                                    {fieldErrors.current_password}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="new-password"
                                className="block text-sm font-medium text-gray-700"
                            >
                                Nova senha
                            </label>
                            <input
                                id="new-password"
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                required
                                className={cn(
                                    "mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1",
                                    fieldErrors.password
                                        ? "border-red-300 focus:border-red-500 focus:ring-red-500"
                                        : "border-gray-300 focus:border-primary-500 focus:ring-primary-500",
                                )}
                            />
                            {fieldErrors.password && (
                                <p className="mt-1 text-sm text-red-600">
                                    {fieldErrors.password}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="password-confirmation"
                                className="block text-sm font-medium text-gray-700"
                            >
                                Confirmar nova senha
                            </label>
                            <input
                                id="password-confirmation"
                                type="password"
                                value={passwordConfirmation}
                                onChange={(e) =>
                                    setPasswordConfirmation(e.target.value)
                                }
                                required
                                className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                            />
                        </div>

                        <div className="flex gap-3 pt-2">
                            <button
                                type="button"
                                onClick={handleCancel}
                                className="flex-1 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors cursor-pointer"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                disabled={mutation.isPending}
                                className={cn(
                                    "flex-1 rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer",
                                    mutation.isPending &&
                                        "opacity-70 cursor-not-allowed",
                                )}
                            >
                                {mutation.isPending
                                    ? "Salvando..."
                                    : "Alterar senha"}
                            </button>
                        </div>
                    </form>
                ) : (
                    !success && (
                        <p className="text-sm text-gray-500">
                            Use uma senha forte com pelo menos 8 caracteres.
                        </p>
                    )
                )}
            </div>
        </section>
    );
}

function RegistrationsSection() {
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ["profile", "registrations", page],
        queryFn: () => profileApi.registrations({ page, per_page: 10 }),
    });

    const registrations = data?.data ?? [];
    const meta = data?.meta;

    return (
        <section className="rounded-lg border border-gray-200 bg-white">
            <div className="border-b border-gray-200 px-6 py-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Calendar className="h-5 w-5 text-gray-400" />
                        <h2 className="text-lg font-semibold text-gray-900">
                            Minhas inscrições
                        </h2>
                    </div>
                    {meta && meta.total > 0 && (
                        <span className="text-sm text-gray-500">
                            {meta.total} inscriç{meta.total > 1 ? "ões" : "ão"}
                        </span>
                    )}
                </div>
            </div>

            <div className="divide-y divide-gray-200">
                {isLoading ? (
                    <div className="flex items-center justify-center py-8">
                        <Loader2 className="h-6 w-6 animate-spin text-gray-400" />
                    </div>
                ) : registrations.length === 0 ? (
                    <div className="px-6 py-8 text-center">
                        <Calendar className="mx-auto h-8 w-8 text-gray-300" />
                        <p className="mt-2 text-sm text-gray-500">
                            Você ainda não se inscreveu em nenhum seminário.
                        </p>
                        <Link
                            to="/apresentacoes"
                            className="mt-4 inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                        >
                            Ver apresentações
                            <ChevronRight className="ml-1 h-4 w-4" />
                        </Link>
                    </div>
                ) : (
                    <>
                        {registrations.map((registration) => (
                            <div
                                key={registration.id}
                                className="flex items-center justify-between px-6 py-4"
                            >
                                <div className="flex-1 min-w-0">
                                    <Link
                                        to={`/seminario/${registration.seminar.slug}`}
                                        className="font-medium text-gray-900 hover:text-primary-600 cursor-pointer"
                                    >
                                        {registration.seminar.name}
                                    </Link>
                                    <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                                        {registration.seminar.scheduled_at && (
                                            <span className="flex items-center gap-1">
                                                <Calendar className="h-3.5 w-3.5" />
                                                {formatDateTime(
                                                    registration.seminar
                                                        .scheduled_at,
                                                )}
                                            </span>
                                        )}
                                        {registration.seminar.location && (
                                            <span className="flex items-center gap-1">
                                                <MapPin className="h-3.5 w-3.5" />
                                                {
                                                    registration.seminar
                                                        .location.name
                                                }
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="ml-4 flex items-center gap-2">
                                    {registration.seminar.is_expired &&
                                        !registration.present && (
                                            <Badge variant="expired">
                                                <X className="mr-1 h-3 w-3" />
                                                Ausente
                                            </Badge>
                                        )}

                                    {registration.present && (
                                        <Badge variant="default">
                                            <Check className="mr-1 h-3 w-3" />
                                            Presente
                                        </Badge>
                                    )}

                                    {!registration.seminar.is_expired &&
                                        !registration.present && (
                                            <Badge variant="default">
                                                Inscrito
                                            </Badge>
                                        )}
                                </div>
                            </div>
                        ))}
                        {meta && meta.last_page > 1 && (
                            <div className="flex items-center justify-between px-6 py-3 bg-gray-50">
                                <span className="text-sm text-gray-500">
                                    Página {meta.current_page} de{" "}
                                    {meta.last_page}
                                </span>
                                <div className="flex items-center gap-2">
                                    <button
                                        onClick={() =>
                                            setPage((p) => Math.max(1, p - 1))
                                        }
                                        disabled={page === 1}
                                        className={cn(
                                            "inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium shadow-sm transition-colors cursor-pointer",
                                            page === 1
                                                ? "text-gray-300 cursor-not-allowed"
                                                : "text-gray-700 hover:bg-gray-50",
                                        )}
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                        Anterior
                                    </button>
                                    <button
                                        onClick={() =>
                                            setPage((p) =>
                                                Math.min(meta.last_page, p + 1),
                                            )
                                        }
                                        disabled={page === meta.last_page}
                                        className={cn(
                                            "inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium shadow-sm transition-colors cursor-pointer",
                                            page === meta.last_page
                                                ? "text-gray-300 cursor-not-allowed"
                                                : "text-gray-700 hover:bg-gray-50",
                                        )}
                                    >
                                        Próximo
                                        <ChevronRight className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>
        </section>
    );
}

function CertificatesSection() {
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ["profile", "certificates", page],
        queryFn: () => profileApi.certificates({ page, per_page: 10 }),
    });

    const certificates = data?.data ?? [];
    const meta = data?.meta;

    return (
        <section className="rounded-lg border border-gray-200 bg-white">
            <div className="border-b border-gray-200 px-6 py-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <FileText className="h-5 w-5 text-gray-400" />
                        <h2 className="text-lg font-semibold text-gray-900">
                            Meus certificados
                        </h2>
                    </div>
                    {meta && meta.total > 0 && (
                        <span className="text-sm text-gray-500">
                            {meta.total} certificado
                            {meta.total !== 1 ? "s" : ""}
                        </span>
                    )}
                </div>
            </div>

            <div className="divide-y divide-gray-200">
                {isLoading ? (
                    <div className="flex items-center justify-center py-8">
                        <Loader2 className="h-6 w-6 animate-spin text-gray-400" />
                    </div>
                ) : certificates.length === 0 ? (
                    <div className="px-6 py-8 text-center">
                        <FileText className="mx-auto h-8 w-8 text-gray-300" />
                        <p className="mt-2 text-sm text-gray-500">
                            Você ainda não possui certificados.
                        </p>
                        <p className="mt-1 text-xs text-gray-400">
                            Certificados são gerados após a confirmação de
                            presença em um seminário.
                        </p>
                    </div>
                ) : (
                    <>
                        {certificates.map((certificate) => (
                            <div
                                key={certificate.id}
                                className="flex items-center justify-between px-6 py-4"
                            >
                                <div className="flex-1 min-w-0">
                                    <Link
                                        to={`/seminario/${certificate.seminar.slug}`}
                                        className="font-medium text-gray-900 hover:text-primary-600 cursor-pointer"
                                    >
                                        {certificate.seminar.name}
                                    </Link>
                                    <div className="mt-1 flex items-center gap-2 text-sm text-gray-500">
                                        {certificate.seminar.seminar_type && (
                                            <Badge variant="default">
                                                {
                                                    certificate.seminar
                                                        .seminar_type.name
                                                }
                                            </Badge>
                                        )}
                                        {certificate.seminar.scheduled_at && (
                                            <span>
                                                {formatDateTime(
                                                    certificate.seminar
                                                        .scheduled_at,
                                                )}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <a
                                        href={`/certificado/${certificate.certificate_code}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        onClick={() =>
                                            analytics.event("certificate_download", {
                                                seminar_id: certificate.seminar.id,
                                            })
                                        }
                                        className="inline-flex items-center gap-1.5 rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer"
                                    >
                                        <Download className="h-3.5 w-3.5" />
                                        Baixar
                                    </a>
                                </div>
                            </div>
                        ))}
                        {meta && meta.last_page > 1 && (
                            <div className="flex items-center justify-between px-6 py-3 bg-gray-50">
                                <span className="text-sm text-gray-500">
                                    Página {meta.current_page} de{" "}
                                    {meta.last_page}
                                </span>
                                <div className="flex items-center gap-2">
                                    <button
                                        onClick={() =>
                                            setPage((p) => Math.max(1, p - 1))
                                        }
                                        disabled={page === 1}
                                        className={cn(
                                            "inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium shadow-sm transition-colors cursor-pointer",
                                            page === 1
                                                ? "text-gray-300 cursor-not-allowed"
                                                : "text-gray-700 hover:bg-gray-50",
                                        )}
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                        Anterior
                                    </button>
                                    <button
                                        onClick={() =>
                                            setPage((p) =>
                                                Math.min(meta.last_page, p + 1),
                                            )
                                        }
                                        disabled={page === meta.last_page}
                                        className={cn(
                                            "inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium shadow-sm transition-colors cursor-pointer",
                                            page === meta.last_page
                                                ? "text-gray-300 cursor-not-allowed"
                                                : "text-gray-700 hover:bg-gray-50",
                                        )}
                                    >
                                        Próximo
                                        <ChevronRight className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>
        </section>
    );
}
