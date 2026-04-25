import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Plus, Pencil, Trash2, RotateCcw, Archive, Search, ShieldCheck } from "lucide-react";
import { toast } from "sonner";
import { useForm, Controller } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { analytics } from "@shared/lib/analytics";
import { useDebouncedSearch } from "@shared/hooks/useDebouncedSearch";
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
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "../../components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "../../components/ui/table";
import { Skeleton } from "../../components/ui/skeleton";
import { Badge } from "../../components/ui/badge";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "../../components/ui/dialog";
import { UserLgpdPanel } from "../../components/UserLgpdPanel";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "../../components/ui/alert-dialog";
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
import { formatDateTime } from "@shared/lib/utils";
import { PageTitle } from "@shared/components/PageTitle";

export default function UserList() {
    const queryClient = useQueryClient();
    const [showTrashed, setShowTrashed] = useState(false);
    const [roleFilter, setRoleFilter] = useState("");
    const [page, setPage] = useState(1);

    const {
        inputValue: searchInput,
        debouncedValue: searchTerm,
        setInputValue: setSearchInput,
        clear: clearSearch,
    } = useDebouncedSearch({
        onDebouncedChange: () => setPage(1),
    });
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [isLgpdDialogOpen, setIsLgpdDialogOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<AdminUser | null>(null);
    const [deletingUser, setDeletingUser] = useState<AdminUser | null>(null);
    const [lgpdUser, setLgpdUser] = useState<AdminUser | null>(null);

    const { data, isLoading } = useQuery({
        queryKey: [
            "admin-users",
            {
                trashed: showTrashed,
                search: searchTerm,
                role: roleFilter,
                page,
            },
        ],
        queryFn: () =>
            usersApi.list({
                trashed: showTrashed,
                search: searchTerm || undefined,
                role: roleFilter || undefined,
                page,
            }),
    });

    const createMutation = useMutation({
        mutationFn: (data: Parameters<typeof usersApi.create>[0]) =>
            usersApi.create(data),
        onSuccess: (response) => {
            queryClient.invalidateQueries({ queryKey: ["admin-users"] });
            toast.success("Usuario criado com sucesso");
            analytics.event("admin_user_create", { user_id: response?.data?.id });
            closeDialog();
        },
        onError: () => {
            toast.error("Erro ao criar usuario");
        },
    });

    const updateMutation = useMutation({
        mutationFn: ({
            id,
            data,
        }: {
            id: number;
            data: Parameters<typeof usersApi.update>[1];
        }) => usersApi.update(id, data),
        onSuccess: (_, variables) => {
            queryClient.invalidateQueries({ queryKey: ["admin-users"] });
            toast.success("Usuario atualizado com sucesso");
            analytics.event("admin_user_update", { user_id: variables.id });
            closeDialog();
        },
        onError: () => {
            toast.error("Erro ao atualizar usuario");
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id: number) => usersApi.delete(id),
        onSuccess: (_, id) => {
            queryClient.invalidateQueries({ queryKey: ["admin-users"] });
            toast.success("Usuario excluido com sucesso");
            analytics.event("admin_user_archive", { user_id: id });
            setIsDeleteDialogOpen(false);
            setDeletingUser(null);
        },
        onError: () => {
            toast.error("Erro ao excluir usuario");
        },
    });

    const restoreMutation = useMutation({
        mutationFn: (id: number) => usersApi.restore(id),
        onSuccess: (_, id) => {
            queryClient.invalidateQueries({ queryKey: ["admin-users"] });
            toast.success("Usuario restaurado com sucesso");
            analytics.event("admin_user_restore", { user_id: id });
        },
        onError: () => {
            toast.error("Erro ao restaurar usuario");
        },
    });

    const users = data?.data ?? [];
    const meta = data?.meta;

    const handleRoleFilter = (value: string) => {
        setRoleFilter(value === "all" ? "" : value);
        setPage(1);
    };

    const handleTrashedToggle = () => {
        setShowTrashed(!showTrashed);
        setPage(1);
    };

    const clearFilters = () => {
        clearSearch();
        setRoleFilter("");
        setPage(1);
    };

    const openCreateDialog = () => {
        setEditingUser(null);
        setIsDialogOpen(true);
    };

    const openEditDialog = (user: AdminUser) => {
        setEditingUser(user);
        setIsDialogOpen(true);
    };

    const openDeleteDialog = (user: AdminUser) => {
        setDeletingUser(user);
        setIsDeleteDialogOpen(true);
    };

    const closeDialog = () => {
        setIsDialogOpen(false);
        setEditingUser(null);
    };

    const ROLE_CONFIG: Record<string, { label: string; variant: "primary" | "secondary" }> = {
        admin: { label: "Admin", variant: "primary" },
        teacher: { label: "Professor", variant: "primary" },
        user: { label: "Usuário", variant: "secondary" },
    };

    const getRoleConfig = (role: string) =>
        ROLE_CONFIG[role] ?? ROLE_CONFIG.user;

    return (
        <div className="space-y-6">
            <PageTitle title="Usuários" />
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Usuarios
                    </h1>
                    <p className="text-muted-foreground">
                        Gerenciar usuarios do sistema
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <Button
                        variant={showTrashed ? "default" : "outline"}
                        onClick={handleTrashedToggle}
                    >
                        <Archive className="h-4 w-4 mr-2" />
                        {showTrashed ? "Ver Ativos" : "Ver Excluidos"}
                    </Button>
                    <Button onClick={openCreateDialog}>
                        <Plus className="h-4 w-4 mr-2" />
                        Novo Usuario
                    </Button>
                </div>
            </div>

            {/* Filters */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap items-end gap-4">
                        <div className="space-y-2 flex-1 min-w-[200px]">
                            <Label htmlFor="search">Buscar</Label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="search"
                                    placeholder="Nome ou email..."
                                    value={searchInput}
                                    onChange={(e) =>
                                        setSearchInput(e.target.value)
                                    }
                                    className="pl-9"
                                />
                            </div>
                        </div>
                        <div className="space-y-2 min-w-[150px]">
                            <Label htmlFor="role-filter">Funcao</Label>
                            <Select
                                value={roleFilter || "all"}
                                onValueChange={handleRoleFilter}
                            >
                                <SelectTrigger id="role-filter">
                                    <SelectValue placeholder="Todas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas</SelectItem>
                                    <SelectItem value="admin">Admin</SelectItem>
                                    <SelectItem value="teacher">
                                        Professor
                                    </SelectItem>
                                    <SelectItem value="user">
                                        Usuário
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        {(searchTerm || roleFilter) && (
                            <Button variant="outline" onClick={clearFilters}>
                                Limpar filtros
                            </Button>
                        )}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle>
                            {showTrashed
                                ? "Usuarios Excluidos"
                                : "Lista de Usuarios"}
                        </CardTitle>
                        {meta && (
                            <span className="text-sm text-muted-foreground">
                                {meta.total} usuarios encontrados
                            </span>
                        )}
                    </div>
                </CardHeader>
                <CardContent>
                    {isLoading ? (
                        <div className="space-y-3">
                            {[...Array(5)].map((_, i) => (
                                <Skeleton key={i} className="h-12 w-full" />
                            ))}
                        </div>
                    ) : users.length === 0 ? (
                        <div className="text-center py-12">
                            <p className="text-muted-foreground">
                                {showTrashed
                                    ? "Nenhum usuario excluido"
                                    : "Nenhum usuario encontrado"}
                            </p>
                            {(searchTerm || roleFilter) && (
                                <Button
                                    variant="link"
                                    onClick={clearFilters}
                                    className="mt-2"
                                >
                                    Limpar filtros
                                </Button>
                            )}
                        </div>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nome</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Funcao</TableHead>
                                        <TableHead>Criado em</TableHead>
                                        <TableHead className="w-24">
                                            Acoes
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell className="font-medium">
                                                {user.name}
                                            </TableCell>
                                            <TableCell>{user.email}</TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    {user.roles.map((role) => {
                                                        const config = getRoleConfig(role);
                                                        return (
                                                            <Badge
                                                                key={role}
                                                                variant={config.variant}
                                                            >
                                                                {config.label}
                                                            </Badge>
                                                        );
                                                    })}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {formatDateTime(
                                                    user.created_at,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    {showTrashed ? (
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                restoreMutation.mutate(
                                                                    user.id,
                                                                )
                                                            }
                                                            disabled={
                                                                restoreMutation.isPending
                                                            }
                                                        >
                                                            <RotateCcw className="h-4 w-4 text-green-500" />
                                                        </Button>
                                                    ) : (
                                                        <>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    openEditDialog(
                                                                        user,
                                                                    )
                                                                }
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                title="Dados LGPD"
                                                                onClick={() => {
                                                                    setLgpdUser(user);
                                                                    setIsLgpdDialogOpen(true);
                                                                }}
                                                            >
                                                                <ShieldCheck className="h-4 w-4 text-blue-500" />
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    openDeleteDialog(
                                                                        user,
                                                                    )
                                                                }
                                                            >
                                                                <Trash2 className="h-4 w-4 text-red-500" />
                                                            </Button>
                                                        </>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            {/* Pagination */}
                            {meta && meta.last_page > 1 && (
                                <div className="flex items-center justify-between mt-4 pt-4 border-t border-border">
                                    <div className="text-sm text-muted-foreground">
                                        Mostrando {meta.from} a {meta.to} de{" "}
                                        {meta.total} usuarios
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                setPage((p) =>
                                                    Math.max(1, p - 1),
                                                )
                                            }
                                            disabled={page === 1}
                                        >
                                            Anterior
                                        </Button>
                                        <span className="text-sm text-muted-foreground">
                                            Pagina {page} de {meta.last_page}
                                        </span>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                setPage((p) =>
                                                    Math.min(
                                                        meta.last_page,
                                                        p + 1,
                                                    ),
                                                )
                                            }
                                            disabled={page === meta.last_page}
                                        >
                                            Proxima
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </CardContent>
            </Card>

            {/* Create/Edit Dialog */}
            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {editingUser ? "Editar Usuario" : "Novo Usuario"}
                        </DialogTitle>
                        <DialogDescription>
                            {editingUser
                                ? "Edite os dados do usuario abaixo"
                                : "Preencha os dados do novo usuario"}
                        </DialogDescription>
                    </DialogHeader>
                    <UserDialogForm
                        key={editingUser?.id ?? "new"}
                        editingUser={editingUser}
                        isSubmitting={
                            createMutation.isPending ||
                            updateMutation.isPending
                        }
                        onCreate={(payload) => createMutation.mutate(payload)}
                        onUpdate={(id, payload) =>
                            updateMutation.mutate({ id, data: payload })
                        }
                        onCancel={closeDialog}
                    />
                </DialogContent>
            </Dialog>

            {/* LGPD Dialog */}
            <Dialog
                open={isLgpdDialogOpen}
                onOpenChange={setIsLgpdDialogOpen}
            >
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            Dados LGPD — {lgpdUser?.name}
                        </DialogTitle>
                        <DialogDescription>
                            Gerencie exportações e anonimização de dados pessoais
                        </DialogDescription>
                    </DialogHeader>
                    {lgpdUser && (
                        <UserLgpdPanel userId={lgpdUser.id} />
                    )}
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <AlertDialog
                open={isDeleteDialogOpen}
                onOpenChange={setIsDeleteDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Excluir usuario?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tem certeza que deseja excluir o usuario "
                            {deletingUser?.name}"? O usuario podera ser
                            restaurado posteriormente.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() =>
                                deletingUser &&
                                deleteMutation.mutate(deletingUser.id)
                            }
                            className="bg-red-500 hover:bg-red-600 text-white"
                        >
                            {deleteMutation.isPending
                                ? "Excluindo..."
                                : "Excluir"}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}

type UserCreatePayload = Parameters<typeof usersApi.create>[0];
type UserUpdatePayload = Parameters<typeof usersApi.update>[1];

interface UserDialogFormProps {
    editingUser: AdminUser | null;
    isSubmitting: boolean;
    onCreate: (payload: UserCreatePayload) => void;
    onUpdate: (id: number, payload: UserUpdatePayload) => void;
    onCancel: () => void;
}

function UserDialogForm({
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
