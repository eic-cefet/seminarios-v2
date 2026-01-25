import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Plus, Pencil, Trash2 } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { locationsApi, type AdminLocation } from "../../api/adminClient";
import { useCRUDListState } from "../../hooks/useCRUDListState";
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
import { PageTitle } from "@shared/components/PageTitle";

const initialFormData = { name: "", max_vacancies: "" };

export default function LocationList() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);

    const {
        isDialogOpen,
        setIsDialogOpen,
        isDeleteDialogOpen,
        setIsDeleteDialogOpen,
        editingItem: editingLocation,
        deletingItem: deletingLocation,
        formData,
        setFormData,
        openCreateDialog,
        openEditDialog,
        openDeleteDialog,
        closeDialog,
        closeDeleteDialog,
    } = useCRUDListState<AdminLocation, typeof initialFormData>({
        initialFormData,
        populateForm: (location) => ({
            name: location.name,
            max_vacancies: location.max_vacancies.toString(),
        }),
    });

    const { data, isLoading } = useQuery({
        queryKey: ["admin-locations", page],
        queryFn: () => locationsApi.list({ page }),
    });

    const createMutation = useMutation({
        mutationFn: (data: { name: string; max_vacancies: number }) =>
            locationsApi.create(data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-locations"] });
            toast.success("Local criado com sucesso");
            closeDialog();
        },
        onError: () => {
            toast.error("Erro ao criar local");
        },
    });

    const updateMutation = useMutation({
        mutationFn: ({
            id,
            data,
        }: {
            id: number;
            data: { name?: string; max_vacancies?: number };
        }) => locationsApi.update(id, data),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-locations"] });
            toast.success("Local atualizado com sucesso");
            closeDialog();
        },
        onError: () => {
            toast.error("Erro ao atualizar local");
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id: number) => locationsApi.delete(id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ["admin-locations"] });
            toast.success("Local excluido com sucesso");
            closeDeleteDialog();
        },
        onError: () => {
            toast.error("Erro ao excluir local");
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const data = {
            name: formData.name,
            max_vacancies: parseInt(formData.max_vacancies, 10),
        };

        if (editingLocation) {
            updateMutation.mutate({ id: editingLocation.id, data });
        } else {
            createMutation.mutate(data);
        }
    };

    const locations = data?.data ?? [];
    const meta = data?.meta;

    return (
        <div className="space-y-6">
            <PageTitle title="Locais" />
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Locais
                    </h1>
                    <p className="text-muted-foreground">
                        Gerenciar locais dos seminarios
                    </p>
                </div>
                <Button onClick={openCreateDialog}>
                    <Plus className="h-4 w-4 mr-2" />
                    Novo Local
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle>Lista de Locais</CardTitle>
                        {meta && (
                            <span className="text-sm text-muted-foreground">
                                {meta.total} locais encontrados
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
                    ) : locations.length === 0 ? (
                        <p className="text-center text-muted-foreground py-8">
                            Nenhum local cadastrado
                        </p>
                    ) : (
                        <>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nome</TableHead>
                                        <TableHead>Capacidade</TableHead>
                                        <TableHead>Seminarios</TableHead>
                                        <TableHead className="w-24">
                                            Acoes
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {locations.map((location) => (
                                        <TableRow key={location.id}>
                                            <TableCell className="font-medium">
                                                {location.name}
                                            </TableCell>
                                            <TableCell>
                                                {location.max_vacancies}
                                            </TableCell>
                                            <TableCell>
                                                {location.seminars_count ?? 0}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            openEditDialog(
                                                                location,
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
                                                                location,
                                                            )
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
                                        {meta.total} locais
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
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingLocation ? "Editar Local" : "Novo Local"}
                        </DialogTitle>
                        <DialogDescription>
                            {editingLocation
                                ? "Edite os dados do local abaixo"
                                : "Preencha os dados do novo local"}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleSubmit}>
                        <div className="space-y-4 py-4">
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
                                    placeholder="Ex: Auditorio Principal"
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="max_vacancies">
                                    Capacidade
                                </Label>
                                <Input
                                    id="max_vacancies"
                                    type="number"
                                    min="1"
                                    value={formData.max_vacancies}
                                    onChange={(e) =>
                                        setFormData({
                                            ...formData,
                                            max_vacancies: e.target.value,
                                        })
                                    }
                                    placeholder="Ex: 100"
                                    required
                                />
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
                        <AlertDialogTitle>Excluir local?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tem certeza que deseja excluir o local "
                            {deletingLocation?.name}"? Esta acao nao pode ser
                            desfeita.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() =>
                                deletingLocation &&
                                deleteMutation.mutate(deletingLocation.id)
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
