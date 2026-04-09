import { coursesApi, profileApi } from "@shared/api/client";
import { cn } from "@shared/lib/utils";
import { analytics } from "@shared/lib/analytics";
import type { User as UserType } from "@shared/types";
import { useMutation, useQuery } from "@tanstack/react-query";
import { GraduationCap } from "lucide-react";
import { useState } from "react";
import { ErrorAlert, SuccessAlert } from "./FormAlerts";
import { useProfileForm } from "./useProfileForm";

interface StudentDataSectionProps {
    user: UserType;
    onUpdate: () => Promise<void>;
}

export function StudentDataSection({ user, onUpdate }: StudentDataSectionProps) {
    const [courseId, setCourseId] = useState(
        user.student_data?.course?.id?.toString() || "",
    );
    const [courseSituation, setCourseSituation] = useState<
        "studying" | "graduated"
    >(user.student_data?.course_situation || "studying");
    const [courseRole, setCourseRole] = useState<
        "Aluno" | "Professor" | "Outro"
    >(user.student_data?.course_role || "Aluno");

    const { isEditing, startEditing, error, fieldErrors, success, mutationCallbacks, handleCancel } =
        useProfileForm({
            onSuccess: async () => {
                analytics.event("profile_student_data_update");
                await onUpdate();
            },
            onCancel: () => {
                setCourseId(user.student_data?.course?.id?.toString() || "");
                setCourseSituation(user.student_data?.course_situation || "studying");
                setCourseRole(user.student_data?.course_role || "Aluno");
            },
        });

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
        ...mutationCallbacks,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate();
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
                            onClick={startEditing}
                            className="text-sm font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                        >
                            Editar
                        </button>
                    )}
                </div>
            </div>

            <div className="px-6 py-4">
                {success && (
                    <SuccessAlert message="Dados acadêmicos atualizados com sucesso!" />
                )}

                {isEditing ? (
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {error && <ErrorAlert message={error} />}

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
