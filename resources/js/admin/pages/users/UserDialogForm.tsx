import { useState } from "react";
import { useForm, Controller } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { hasRole, ROLES } from "@shared/lib/roles";
import { usersApi, type AdminUser } from "../../api/adminClient";
import { AiTextToolbar } from "../../components/AiTextToolbar";
import {
    createUserFormSchema,
    updateUserFormSchema,
    userFormDefaults,
    type UserFormData,
} from "./UserList.schema";
import { Button } from "../../components/ui/button";
import { DialogFooter } from "../../components/ui/dialog";
import { Input } from "../../components/ui/input";
import { Label } from "../../components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "../../components/ui/select";
import { Separator } from "../../components/ui/separator";

type UserCreatePayload = Parameters<typeof usersApi.create>[0];
type UserUpdatePayload = Parameters<typeof usersApi.update>[1];

interface UserDialogFormProps {
    editingUser: AdminUser | null;
    isSubmitting: boolean;
    onCreate: (payload: UserCreatePayload) => void;
    onUpdate: (id: number, payload: UserUpdatePayload) => void;
    onCancel: () => void;
}

export function UserDialogForm({
    editingUser,
    isSubmitting,
    onCreate,
    onUpdate,
    onCancel,
}: UserDialogFormProps) {
    const [aiLoading, setAiLoading] = useState<boolean>(false);

    const formSchema = editingUser ? updateUserFormSchema : createUserFormSchema;

    const initialValues: UserFormData = editingUser
        ? {
              name: editingUser.name,
              email: editingUser.email,
              password: "",
              role: hasRole(editingUser.roles, ROLES.ADMIN)
                  ? "admin"
                  : hasRole(editingUser.roles, ROLES.TEACHER)
                    ? "teacher"
                    : "user",
              student_data: {
                  course_name: editingUser.student_data?.course_name || "",
                  course_situation:
                      editingUser.student_data?.course_situation || "",
                  course_role: editingUser.student_data?.course_role || "",
              },
              speaker_data: {
                  slug: editingUser.speaker_data?.slug || "",
                  institution: editingUser.speaker_data?.institution || "",
                  description: editingUser.speaker_data?.description || "",
              },
          }
        : userFormDefaults;

    const {
        register,
        handleSubmit,
        watch,
        setValue,
        control,
        formState: { errors },
    } = useForm<UserFormData>({
        resolver: zodResolver(formSchema),
        defaultValues: initialValues,
    });

    const onSubmit = (data: UserFormData) => {
        const hasStudentData = Boolean(
            data.student_data.course_name ||
                data.student_data.course_situation ||
                data.student_data.course_role,
        );

        const hasSpeakerData = Boolean(
            data.speaker_data.institution || data.speaker_data.description,
        );

        if (editingUser) {
            onUpdate(editingUser.id, {
                name: data.name,
                email: data.email,
                password: data.password || undefined,
                role: data.role,
                student_data: hasStudentData ? data.student_data : null,
                speaker_data: hasSpeakerData ? data.speaker_data : null,
            });
        } else {
            onCreate({
                name: data.name,
                email: data.email,
                password: data.password,
                role: data.role,
                student_data: hasStudentData ? data.student_data : undefined,
                speaker_data: hasSpeakerData ? data.speaker_data : undefined,
            });
        }
    };

    return (
        <form onSubmit={handleSubmit(onSubmit)} noValidate>
            <div className="space-y-6 py-4">
                {/* Basic Info */}
                <div className="space-y-4">
                    <h3 className="font-medium">Informacoes Basicas</h3>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nome</Label>
                            <Input id="name" {...register("name")} />
                            {errors.name && (
                                <p className="text-sm text-red-500">
                                    {errors.name.message}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                {...register("email")}
                            />
                            {errors.email && (
                                <p className="text-sm text-red-500">
                                    {errors.email.message}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="password">
                                Senha{" "}
                                {editingUser && "(deixe vazio para manter)"}
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                {...register("password")}
                                required={!editingUser}
                                minLength={8}
                            />
                            {errors.password && (
                                <p className="text-sm text-red-500">
                                    {errors.password.message}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="role">Funcao</Label>
                            <Controller
                                control={control}
                                name="role"
                                render={({ field }) => (
                                    <Select
                                        value={field.value}
                                        onValueChange={field.onChange}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="user">
                                                Usuário
                                            </SelectItem>
                                            <SelectItem value="teacher">
                                                Professor
                                            </SelectItem>
                                            <SelectItem value="admin">
                                                Administrador
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                        </div>
                    </div>
                </div>

                <Separator />

                {/* Student Data */}
                <div className="space-y-4">
                    <h3 className="font-medium">Dados de Aluno (opcional)</h3>
                    <div className="grid grid-cols-3 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="course_name">Curso</Label>
                            <Input
                                id="course_name"
                                {...register("student_data.course_name")}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="course_situation">Situacao</Label>
                            <Controller
                                control={control}
                                name="student_data.course_situation"
                                render={({ field }) => (
                                    <Select
                                        value={field.value}
                                        onValueChange={field.onChange}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecione" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="studying">
                                                Cursando
                                            </SelectItem>
                                            <SelectItem value="graduated">
                                                Formado
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="course_role">Tipo</Label>
                            <Input
                                id="course_role"
                                {...register("student_data.course_role")}
                            />
                        </div>
                    </div>
                </div>

                <Separator />

                {/* Speaker Data */}
                <div className="space-y-4">
                    <h3 className="font-medium">
                        Dados de Palestrante (opcional)
                    </h3>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="slug">Slug (URL)</Label>
                            <Input
                                id="slug"
                                {...register("speaker_data.slug")}
                                placeholder="gerado-automaticamente"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="institution">Instituicao</Label>
                            <Input
                                id="institution"
                                {...register("speaker_data.institution")}
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label htmlFor="description">Descricao</Label>
                            <AiTextToolbar
                                value={watch("speaker_data.description") || ""}
                                onChange={(value) =>
                                    setValue("speaker_data.description", value)
                                }
                                onLoadingChange={setAiLoading}
                            />
                        </div>
                        <textarea
                            id="description"
                            className="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:opacity-50 disabled:cursor-not-allowed"
                            {...register("speaker_data.description")}
                            disabled={aiLoading}
                        />
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button type="button" variant="outline" onClick={onCancel}>
                    Cancelar
                </Button>
                <Button type="submit" disabled={isSubmitting}>
                    {isSubmitting ? "Salvando..." : "Salvar"}
                </Button>
            </DialogFooter>
        </form>
    );
}
