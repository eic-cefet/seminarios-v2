import { formatDateTime } from "@shared/lib/utils";
import { analytics } from "@shared/lib/analytics";
import { useDebouncedSearch } from "@shared/hooks/useDebouncedSearch";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Calendar, Pencil, Plus, QrCode, Search, Trash2 } from "lucide-react";
import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { toast } from "sonner";
import { PageTitle } from "@shared/components/PageTitle";
import { seminarsApi, type AdminSeminar } from "../../api/adminClient";
import { PresenceLinkModal } from "../../components/PresenceLinkModal";
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
import { Badge } from "../../components/ui/badge";
import { Button } from "../../components/ui/button";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "../../components/ui/card";
import { Input } from "../../components/ui/input";
import { Label } from "../../components/ui/label";
import { Skeleton } from "../../components/ui/skeleton";
import { Switch } from "../../components/ui/switch";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "../../components/ui/table";

export default function SeminarList() {
    const queryClient = useQueryClient();
    const navigate = useNavigate();
    const [activeFilter, setActiveFilter] = useState<boolean | undefined>(
        undefined,
    );
    const [upcomingFilter, setUpcomingFilter] = useState(false);
    const [page, setPage] = useState(1);

    const {
        inputValue: searchInput,
        debouncedValue: searchTerm,
        setInputValue: setSearchInput,
        clear: clearSearch,
    } = useDebouncedSearch({
        onDebouncedChange: () => setPage(1),
    });
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
    const [deletingSeminar, setDeletingSeminar] = useState<AdminSeminar | null>(
        null,
    );
    const [isPresenceLinkModalOpen, setIsPresenceLinkModalOpen] =
        useState(false);
    const [selectedSeminar, setSelectedSeminar] = useState<AdminSeminar | null>(
        null,
    );

    const { data, isLoading } = useQuery({
        queryKey: [
            "admin-seminars",
            {
                search: searchTerm,
                active: activeFilter,
                upcoming: upcomingFilter,
                page,
            },
        ],
        queryFn: () =>
            seminarsApi.list({
                search: searchTerm || undefined,
                active: activeFilter,
                upcoming: upcomingFilter,
                page,
            }),
    });

    const deleteMutation = useMutation({
        mutationFn: (id: number) => seminarsApi.delete(id),
        onSuccess: (_, id) => {
            queryClient.invalidateQueries({ queryKey: ["admin-seminars"] });
            toast.success("Seminário excluído com sucesso");
            analytics.event("admin_seminar_delete", { seminar_id: id });
            setIsDeleteDialogOpen(false);
            setDeletingSeminar(null);
        },
        onError: () => {
            toast.error("Erro ao excluir seminário");
        },
    });

    const seminars = data?.data ?? [];
    const meta = data?.meta;


    const handleActiveFilterChange = (checked: boolean) => {
        setActiveFilter(checked ? true : undefined);
        setPage(1);
    };

    const handleUpcomingFilterChange = (checked: boolean) => {
        setUpcomingFilter(checked);
        setPage(1);
    };

    const clearFilters = () => {
        clearSearch();
        setActiveFilter(undefined);
        setUpcomingFilter(false);
        setPage(1);
    };

    const openDeleteDialog = (seminar: AdminSeminar) => {
        setDeletingSeminar(seminar);
        setIsDeleteDialogOpen(true);
    };

    const openPresenceLinkModal = (seminar: AdminSeminar) => {
        setSelectedSeminar(seminar);
        setIsPresenceLinkModalOpen(true);
    };

    return (
        <>
            <PageTitle title="Seminários" />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            Seminários
                        </h1>
                        <p className="text-muted-foreground">
                            Gerenciar seminários do sistema
                        </p>
                    </div>
                    <Button onClick={() => navigate("/seminars/new")}>
                        <Plus className="h-4 w-4 mr-2" />
                        Novo Seminário
                    </Button>
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
                                        placeholder="Nome do seminário..."
                                        value={searchInput}
                                        onChange={(e) =>
                                            setSearchInput(e.target.value)
                                        }
                                        className="pl-9"
                                    />
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <Switch
                                    id="active-filter"
                                    checked={activeFilter === true}
                                    onCheckedChange={handleActiveFilterChange}
                                />
                                <Label htmlFor="active-filter">
                                    Apenas ativos
                                </Label>
                            </div>
                            <div className="flex items-center gap-2">
                                <Switch
                                    id="upcoming-filter"
                                    checked={upcomingFilter}
                                    onCheckedChange={handleUpcomingFilterChange}
                                />
                                <Label htmlFor="upcoming-filter">
                                    Apenas futuros
                                </Label>
                            </div>
                            {(searchTerm || activeFilter || upcomingFilter) && (
                                <Button
                                    variant="outline"
                                    onClick={clearFilters}
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
                            <CardTitle>Lista de Seminários</CardTitle>
                            {meta && (
                                <span className="text-sm text-muted-foreground">
                                    {meta.total} seminários encontrados
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
                        ) : seminars.length === 0 ? (
                            <div className="text-center py-12">
                                <p className="text-muted-foreground">
                                    Nenhum seminário encontrado
                                </p>
                                {(searchTerm ||
                                    activeFilter ||
                                    upcomingFilter) && (
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
                                            <TableHead>Data</TableHead>
                                            <TableHead>Local</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="w-24">
                                                Ações
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {seminars.map((seminar) => (
                                            <TableRow key={seminar.id}>
                                                <TableCell className="font-medium">
                                                    {seminar.name}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-1 text-sm">
                                                        <Calendar className="h-3 w-3" />
                                                        {formatDateTime(
                                                            seminar.scheduled_at,
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {seminar.location?.name ||
                                                        "-"}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        className={
                                                            seminar.active
                                                                ? "bg-white text-black border border-border"
                                                                : "bg-red-500 text-white"
                                                        }
                                                    >
                                                        {seminar.active
                                                            ? "Ativo"
                                                            : "Inativo"}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                openPresenceLinkModal(
                                                                    seminar,
                                                                )
                                                            }
                                                            title="Link de Presença (QR Code)"
                                                        >
                                                            <QrCode className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                navigate(
                                                                    `/seminars/${seminar.id}/edit`,
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
                                                                    seminar,
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
                                            {meta.total} seminários
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
                                                Página {page} de{" "}
                                                {meta.last_page}
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
                                                disabled={
                                                    page === meta.last_page
                                                }
                                            >
                                                Próxima
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>

                {/* Delete Confirmation Dialog */}
                <AlertDialog
                    open={isDeleteDialogOpen}
                    onOpenChange={setIsDeleteDialogOpen}
                >
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                Excluir seminário?
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                Tem certeza que deseja excluir o seminário "
                                {deletingSeminar?.name}"? Esta ação pode ser
                                revertida.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Cancelar</AlertDialogCancel>
                            <AlertDialogAction
                                onClick={() =>
                                    deletingSeminar &&
                                    deleteMutation.mutate(deletingSeminar.id)
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

                {/* Presence Link Modal */}
                {selectedSeminar && (
                    <PresenceLinkModal
                        open={isPresenceLinkModalOpen}
                        onClose={() => {
                            setIsPresenceLinkModalOpen(false);
                            setSelectedSeminar(null);
                        }}
                        seminarId={selectedSeminar.id}
                        seminarName={selectedSeminar.name}
                    />
                )}
            </div>
        </>
    );
}
