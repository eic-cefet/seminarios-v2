import { zodResolver } from "@hookform/resolvers/zod";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Plus, Pencil, Trash2, Megaphone } from "lucide-react";
import { useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { workshopsApi, type AdminWorkshop } from "../../api/adminClient";
import { useCRUDListState } from "../../hooks/useCRUDListState";
import {
    workshopFormDefaults,
    workshopFormSchema,
    type WorkshopFormData,
} from "./WorkshopList.schema";
import { useDebouncedSearch } from "@shared/hooks/useDebouncedSearch";
import { SeminarMultiSelect } from "../../components/SeminarMultiSelect";
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
import { MarkdownEditor } from "../../components/MarkdownEditor";
import { PageTitle } from "@shared/components/PageTitle";
import { formatDateTime } from "@shared/lib/utils";

// The announcement feature shipped on 2026-04-26. Workshops created before this
// date predate the feature and could spam users about content they may have
// already seen — only show the announce button for workshops created on or
// after the cutoff. Backend enforces the same rule.
const ANNOUNCE_CUTOFF = new Date("2026-04-26T00:00:00Z");

function canAnnounce(workshop: { created_at: string }): boolean {
    return new Date(workshop.created_at) >= ANNOUNCE_CUTOFF;
}

export default function WorkshopList() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);

    const {
        inputValue: searchInput,
        debouncedValue: searchTerm,
        setInputValue: setSearchInput,
        clear: clearSearch,
    } = useDebouncedSearch({
        onDebouncedChange: () => setPage(1),
    });

    const {
        register,
        handleSubmit,
        watch,
        setValue,
        reset,
        formState: { errors },
    } = useForm<WorkshopFormData>({
        resolver: zodResolver(workshopFormSchema),
        defaultValues: workshopFormDefaults,
    });

    const {
        isDialogOpen,
        setIsDialogOpen,
        isDeleteDialogOpen,
        setIsDeleteDialogOpen,
        editingItem: editingWorkshop,
        deletingItem: deletingWorkshop,
        openCreateDialog: openCreateDialogBase,
        openEditDialog: openEditDialogBase,
        openDeleteDialog,
        closeDialog: closeDialogBase,
        closeDeleteDialog,
    } = useCRUDListState<AdminWorkshop, WorkshopFormData>({
        initialFormData: workshopFormDefaults,
        populateForm: (workshop) => ({
            name: workshop.name,
            description: workshop.description || "",
            seminar_ids: [],
        }),
    });

    const [isAnnounceDialogOpen, setIsAnnounceDialogOpen] = useState(false);
    const [announcingWorkshop, setAnnouncingWorkshop] =
        useState<AdminWorkshop | null>(null);

    const openAnnounceDialog = (workshop: AdminWorkshop) => {
        setAnnouncingWorkshop(workshop);
        setIsAnnounceDialogOpen(true);
    };

    const closeAnnounceDialog = () => {
        setIsAnnounceDialogOpen(false);
        setAnnouncingWorkshop(null);
    };

    const openCreateDialog = () => {
        reset(workshopFormDefaults);
        openCreateDialogBase();
    };

    const openEditDialog = (workshop: AdminWorkshop) => {
        reset({
            name: workshop.name,
            description: workshop.description || "",
            seminar_ids: [],
        });
        openEditDialogBase(workshop);
    };

    const closeDialog = () => {
        reset(workshopFormDefaults);
        closeDialogBase();
    };

    const handleClearFilters = () => {
        clearSearch();
        setPage(1);
    };

    const hasFilters = searchTerm !== "";

    const { data, isLoading } = useQuery({
        queryKey: ["admin-workshops", { search: searchTerm, page }],
        queryFn: () =>
            workshopsApi.list({ search: searchTerm || undefined, page }),
    });

    // Fetch full workshop data when editing (to get seminars)
    const { data: workshopDetail } = useQuery({
        queryKey: ["admin-workshop", editingWorkshop?.slug],
        queryFn: () => workshopsApi.get(editingWorkshop!.slug),
        enabled: !!editingWorkshop?.slug,
    });

    // Update form when workshop detail loads
    useEffect(() => {
        if (workshopDetail?.data && editingWorkshop) {
            reset({
                name: workshopDetail.data.name,
                description: workshopDetail.data.description || "",
                seminar_ids:
                    workshopDetail.data.seminars?.map((s) => s.id) || [],
            });
        }
    }, [workshopDetail, editingWorkshop, reset]);

    const createMutation = useMutation({
        mutationFn: (data: WorkshopFormData) => workshopsApi.create(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-workshops"] });
            toast.success("Workshop criado com sucesso");
            closeDialog();
        },
        onError: () => {
            toast.error("Erro ao criar workshop");
        },
    });

    const updateMutation = useMutation({
        mutationFn: ({ slug, data }: { slug: string; data: WorkshopFormData }) =>
            workshopsApi.update(slug, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-workshops"] });
            queryClient.invalidateQueries({ queryKey: ["admin-workshop"] });
            toast.success("Workshop atualizado com sucesso");
            closeDialog();
        },
        onError: () => {
            toast.error("Erro ao atualizar workshop");
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (slug: string) => workshopsApi.delete(slug),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-workshops"] });
            toast.success("Workshop excluido com sucesso");
            closeDeleteDialog();
        },
        onError: (error: Error) => {
            if (
                error.message.includes("associado") ||
                error.message.includes("seminarios")
            ) {
                toast.error("Este workshop possui seminarios associados");
            } else {
                toast.error("Erro ao excluir workshop");
            }
        },
    });

    const announceMutation = useMutation({
        mutationFn: (id: number) => workshopsApi.announce(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-workshops"] });
            toast.success("Workshop anunciado com sucesso");
            closeAnnounceDialog();
        },
        onError: () => {
            toast.error("Erro ao anunciar workshop");
        },
    });

    const workshops = data?.data ?? [];
    const meta = data?.meta;

    const onSubmit = (data: WorkshopFormData) => {
        if (editingWorkshop) {
            updateMutation.mutate({ slug: editingWorkshop.slug, data });
        } else {
            createMutation.mutate(data);
        }
    };

    return (
        <div className="space-y-6">
            <PageTitle title="Workshops" />
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Workshops
                    </h1>
                    <p className="text-muted-foreground">
                        Gerenciar workshops e seus seminarios
                    </p>
                </div>
                <Button onClick={openCreateDialog}>
                    <Plus className="h-4 w-4 mr-2" />
                    Novo Workshop
                </Button>
            </div>

            {/* Filters */}
            <Card>
                <CardContent className="pt-6">
                    <div className="flex items-center gap-4">
                        <div className="flex-1">
                            <Input
                                placeholder="Pesquisar por nome..."
                                value={searchInput}
                                onChange={(e) => setSearchInput(e.target.value)}
                                className="max-w-sm"
                            />
                        </div>
                        {hasFilters && (
                            <Button
                                variant="outline"
                                onClick={handleClearFilters}
                            >
                                Limpar filtros
                            </Button>
                        )}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle>Lista de Workshops</CardTitle>
                        {meta && (
                            <span className="text-sm text-muted-foreground">
                                {meta.total} workshops encontrados
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
                    ) : workshops.length === 0 ? (
                        <p className="text-center text-muted-foreground py-8">
                            Nenhum workshop cadastrado
                        </p>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nome</TableHead>
                                        <TableHead>Descricao</TableHead>
                                        <TableHead>Seminarios</TableHead>
                                        <TableHead className="w-24">
                                            Acoes
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {workshops.map((workshop) => (
                                        <TableRow key={workshop.id}>
                                            <TableCell className="font-medium">
                                                {workshop.name}
                                            </TableCell>
                                            <TableCell className="max-w-xs truncate text-muted-foreground">
                                                {workshop.description || "-"}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {workshop.seminars_count ??
                                                        0}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            openEditDialog(
                                                                workshop,
                                                            )
                                                        }
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    {workshop.announcement_sent_at ? (
                                                        <Badge variant="secondary">
                                                            Anunciado em{" "}
                                                            {formatDateTime(
                                                                workshop.announcement_sent_at,
                                                            )}
                                                        </Badge>
                                                    ) : canAnnounce(workshop) ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                openAnnounceDialog(
                                                                    workshop,
                                                                )
                                                            }
                                                        >
                                                            <Megaphone className="h-4 w-4 mr-2" />
                                                            Anunciar workshop
                                                        </Button>
                                                    ) : null}
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            openDeleteDialog(
                                                                workshop,
                                                            )
                                                        }
                                                        disabled={
                                                            (workshop.seminars_count ??
                                                                0) > 0
                                                        }
                                                    >
                                                        <Trash2 className="h-4 w-4 text-red-500" />
                                                    </Button>
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
                                        {meta.total} workshops
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
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingWorkshop
                                ? "Editar Workshop"
                                : "Novo Workshop"}
                        </DialogTitle>
                        <DialogDescription>
                            {editingWorkshop
                                ? "Edite os dados do workshop abaixo"
                                : "Preencha os dados do novo workshop"}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleSubmit(onSubmit)} noValidate>
                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Nome *</Label>
                                <Input
                                    id="name"
                                    {...register("name")}
                                    placeholder="Ex: Workshop de Machine Learning"
                                />
                                {errors.name && (
                                    <p className="text-sm text-red-500">
                                        {errors.name.message}
                                    </p>
                                )}
                            </div>

                            <MarkdownEditor
                                label="Descricao"
                                value={watch("description") || ""}
                                onChange={(value) =>
                                    setValue("description", value)
                                }
                            />

                            <SeminarMultiSelect
                                label="Seminarios"
                                value={watch("seminar_ids")}
                                onChange={(ids) =>
                                    setValue("seminar_ids", ids)
                                }
                                workshopId={editingWorkshop?.id}
                                initialSeminars={workshopDetail?.data?.seminars}
                            />
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
                        <AlertDialogTitle>Excluir workshop?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tem certeza que deseja excluir o workshop "
                            {deletingWorkshop?.name}"? Esta acao nao pode ser
                            desfeita.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() =>
                                deletingWorkshop &&
                                deleteMutation.mutate(deletingWorkshop.slug)
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
            {/* Announce Confirmation Dialog */}
            <AlertDialog
                open={isAnnounceDialogOpen}
                onOpenChange={setIsAnnounceDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Anunciar workshop?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tem certeza que deseja anunciar o workshop "
                            {announcingWorkshop?.name}"? Todos os usuarios serao
                            notificados por e-mail. Esta acao so pode ser feita
                            uma vez.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={closeAnnounceDialog}>
                            Cancelar
                        </AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() =>
                                announcingWorkshop &&
                                announceMutation.mutate(announcingWorkshop.id)
                            }
                            disabled={announceMutation.isPending}
                        >
                            {announceMutation.isPending
                                ? "Anunciando..."
                                : "Anunciar"}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
