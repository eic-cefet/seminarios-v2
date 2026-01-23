import { useState, useRef, useEffect } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Plus, Pencil, Trash2, RotateCcw, Archive, Search } from "lucide-react";
import { toast } from "sonner";
import { debounce } from "lodash";
import { usersApi, type AdminUser } from "../../api/adminClient";
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

interface UserFormData {
    name: string;
    email: string;
    password: string;
    role: "admin" | "teacher" | "user";
    student_data: {
        course_name: string;
        course_situation: string;
        course_role: string;
    };
    speaker_data: {
        slug: string;
        institution: string;
        description: string;
    };
}

const initialFormData: UserFormData = {
    name: "",
    email: "",
    password: "",
    role: "user",
    student_data: { course_name: "", course_situation: "", course_role: "" },
    speaker_data: { slug: "", institution: "", description: "" },
};

export default function UserList() {
    const queryClient = useQueryClient();
    const [showTrashed, setShowTrashed] = useState(false);
    const [searchInput, setSearchInput] = useState("");
    const [searchTerm, setSearchTerm] = useState("");
    const [roleFilter, setRoleFilter] = useState("");
    const [page, setPage] = useState(1);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<AdminUser | null>(null);
    const [deletingUser, setDeletingUser] = useState<AdminUser | null>(null);
    const [formData, setFormData] = useState<UserFormData>(initialFormData);

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
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-users"] });
            toast.success("Usuario criado com sucesso");
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
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-users"] });
            toast.success("Usuario atualizado com sucesso");
            closeDialog();
        },
        onError: () => {
            toast.error("Erro ao atualizar usuario");
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id: number) => usersApi.delete(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-users"] });
            toast.success("Usuario excluido com sucesso");
            setIsDeleteDialogOpen(false);
            setDeletingUser(null);
        },
        onError: () => {
            toast.error("Erro ao excluir usuario");
        },
    });

    const restoreMutation = useMutation({
        mutationFn: (id: number) => usersApi.restore(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-users"] });
            toast.success("Usuario restaurado com sucesso");
        },
        onError: () => {
            toast.error("Erro ao restaurar usuario");
        },
    });

    const users = data?.data ?? [];
    const meta = data?.meta;

    // Debounced search handler
    const debouncedSearch = useRef(
        debounce((value: string) => {
            setSearchTerm(value);
            setPage(1);
        }, 500),
    ).current;

    // Cleanup debounce on unmount
    useEffect(() => {
        return () => {
            debouncedSearch.cancel();
        };
    }, [debouncedSearch]);

    const handleSearch = (value: string) => {
        setSearchInput(value);
        debouncedSearch(value);
    };

    const handleRoleFilter = (value: string) => {
        setRoleFilter(value === "all" ? "" : value);
        setPage(1);
    };

    const handleTrashedToggle = () => {
        setShowTrashed(!showTrashed);
        setPage(1);
    };

    const clearFilters = () => {
        setSearchInput("");
        setSearchTerm("");
        setRoleFilter("");
        setPage(1);
    };

    const openCreateDialog = () => {
        setEditingUser(null);
        setFormData(initialFormData);
        setIsDialogOpen(true);
    };

    const openEditDialog = (user: AdminUser) => {
        setEditingUser(user);
        setFormData({
            name: user.name,
            email: user.email,
            password: "",
            role: (user.roles[0] || "user") as "admin" | "teacher" | "user",
            student_data: {
                course_name: user.student_data?.course_name || "",
                course_situation: user.student_data?.course_situation || "",
                course_role: user.student_data?.course_role || "",
            },
            speaker_data: {
                slug: user.speaker_data?.slug || "",
                institution: user.speaker_data?.institution || "",
                description: user.speaker_data?.description || "",
            },
        });
        setIsDialogOpen(true);
    };

    const openDeleteDialog = (user: AdminUser) => {
        setDeletingUser(user);
        setIsDeleteDialogOpen(true);
    };

    const closeDialog = () => {
        setIsDialogOpen(false);
        setEditingUser(null);
        setFormData(initialFormData);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const hasStudentData =
            formData.student_data.course_name ||
            formData.student_data.course_situation ||
            formData.student_data.course_role;

        const hasSpeakerData =
            formData.speaker_data.institution ||
            formData.speaker_data.description;

        if (editingUser) {
            updateMutation.mutate({
                id: editingUser.id,
                data: {
                    name: formData.name,
                    email: formData.email,
                    password: formData.password || undefined,
                    role: formData.role,
                    student_data: hasStudentData ? formData.student_data : null,
                    speaker_data: hasSpeakerData ? formData.speaker_data : null,
                },
            });
        } else {
            createMutation.mutate({
                name: formData.name,
                email: formData.email,
                password: formData.password,
                role: formData.role,
                student_data: hasStudentData
                    ? formData.student_data
                    : undefined,
                speaker_data: hasSpeakerData
                    ? formData.speaker_data
                    : undefined,
            });
        }
    };

    const getRoleBadgeVariant = (role: string) => {
        switch (role) {
            case "admin":
            case "teacher":
                return "primary";
            case "user":
                return "secondary";
            default:
                return "secondary";
        }
    };

    const getRoleLabel = (role: string) => {
        switch (role) {
            case "admin":
                return "Admin";
            case "teacher":
                return "Professor";
            case "user":
                return "Usuário";
            default:
                return "Usuário";
        }
    };

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
                                    placeholder="Nome, email ou username..."
                                    value={searchInput}
                                    onChange={(e) =>
                                        handleSearch(e.target.value)
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
                                                    {user.roles.map((role) => (
                                                        <Badge
                                                            key={role}
                                                            variant={getRoleBadgeVariant(
                                                                role,
                                                            )}
                                                        >
                                                            {getRoleLabel(role)}
                                                        </Badge>
                                                    ))}
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
                    <form onSubmit={handleSubmit}>
                        <div className="space-y-6 py-4">
                            {/* Basic Info */}
                            <div className="space-y-4">
                                <h3 className="font-medium">
                                    Informacoes Basicas
                                </h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nome</Label>
                                        <Input
                                            id="name"
                                            value={formData.name}
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    name: e.target.value,
                                                })
                                            }
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={formData.email}
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    email: e.target.value,
                                                })
                                            }
                                            required
                                        />
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="password">
                                            Senha{" "}
                                            {editingUser &&
                                                "(deixe vazio para manter)"}
                                        </Label>
                                        <Input
                                            id="password"
                                            type="password"
                                            value={formData.password}
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    password: e.target.value,
                                                })
                                            }
                                            required={!editingUser}
                                            minLength={8}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="role">Funcao</Label>
                                        <Select
                                            value={formData.role}
                                            onValueChange={(value) =>
                                                setFormData({
                                                    ...formData,
                                                    role: value as
                                                        | "admin"
                                                        | "teacher"
                                                        | "user",
                                                })
                                            }
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
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            {/* Student Data */}
                            <div className="space-y-4">
                                <h3 className="font-medium">
                                    Dados de Aluno (opcional)
                                </h3>
                                <div className="grid grid-cols-3 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="course_name">
                                            Curso
                                        </Label>
                                        <Input
                                            id="course_name"
                                            value={
                                                formData.student_data
                                                    .course_name
                                            }
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    student_data: {
                                                        ...formData.student_data,
                                                        course_name:
                                                            e.target.value,
                                                    },
                                                })
                                            }
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="course_situation">
                                            Situacao
                                        </Label>
                                        <Select
                                            value={
                                                formData.student_data
                                                    .course_situation
                                            }
                                            onValueChange={(value) =>
                                                setFormData({
                                                    ...formData,
                                                    student_data: {
                                                        ...formData.student_data,
                                                        course_situation: value,
                                                    },
                                                })
                                            }
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
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="course_role">
                                            Tipo
                                        </Label>
                                        <Input
                                            id="course_role"
                                            value={
                                                formData.student_data
                                                    .course_role
                                            }
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    student_data: {
                                                        ...formData.student_data,
                                                        course_role:
                                                            e.target.value,
                                                    },
                                                })
                                            }
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
                                            value={formData.speaker_data.slug}
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    speaker_data: {
                                                        ...formData.speaker_data,
                                                        slug: e.target.value,
                                                    },
                                                })
                                            }
                                            placeholder="gerado-automaticamente"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="institution">
                                            Instituicao
                                        </Label>
                                        <Input
                                            id="institution"
                                            value={
                                                formData.speaker_data
                                                    .institution
                                            }
                                            onChange={(e) =>
                                                setFormData({
                                                    ...formData,
                                                    speaker_data: {
                                                        ...formData.speaker_data,
                                                        institution:
                                                            e.target.value,
                                                    },
                                                })
                                            }
                                        />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="description">
                                        Descricao
                                    </Label>
                                    <textarea
                                        id="description"
                                        className="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        value={
                                            formData.speaker_data.description
                                        }
                                        onChange={(e) =>
                                            setFormData({
                                                ...formData,
                                                speaker_data: {
                                                    ...formData.speaker_data,
                                                    description: e.target.value,
                                                },
                                            })
                                        }
                                    />
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={closeDialog}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={
                                    createMutation.isPending ||
                                    updateMutation.isPending
                                }
                            >
                                {createMutation.isPending ||
                                updateMutation.isPending
                                    ? "Salvando..."
                                    : "Salvar"}
                            </Button>
                        </DialogFooter>
                    </form>
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
                            className="bg-red-500 hover:bg-red-600"
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
