import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Search, Calendar, User, Mail, UserPlus } from "lucide-react";
import { toast } from "sonner";
import { analytics } from "@shared/lib/analytics";
import { useDebouncedSearch } from "@shared/hooks/useDebouncedSearch";
import {
    registrationsApi,
    type AdminRegistration,
} from "../../api/adminClient";
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
import { Switch } from "../../components/ui/switch";
import { Input } from "../../components/ui/input";
import { Label } from "../../components/ui/label";
import {
    SeminarCombobox,
    type SeminarOption,
} from "../../components/SeminarCombobox";
import { AddRegistrationsModal } from "../../components/AddRegistrationsModal";
import { Badge } from "../../components/ui/badge";
import { Button } from "../../components/ui/button";
import { PageTitle } from "@shared/components/PageTitle";
import { Pagination } from "@shared/components/Pagination";
import { formatDateTime } from "@shared/lib/utils";

export default function RegistrationList() {
    const queryClient = useQueryClient();
    const [selectedSeminar, setSelectedSeminar] =
        useState<SeminarOption | null>(null);
    const selectedSeminarId = selectedSeminar ? String(selectedSeminar.id) : "";
    const [page, setPage] = useState(1);
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);

    const {
        inputValue: searchInput,
        debouncedValue: searchTerm,
        setInputValue: setSearchInput,
        clear: clearSearch,
    } = useDebouncedSearch({
        onDebouncedChange: () => setPage(1),
    });

    // Fetch registrations
    const { data: registrationsData, isLoading: isLoadingRegistrations } =
        useQuery({
            queryKey: [
                "admin-registrations",
                selectedSeminarId,
                searchTerm,
                page,
            ],
            queryFn: () =>
                registrationsApi.list({
                    page,
                    seminar_id: selectedSeminar?.id,
                    search: searchTerm || undefined,
                }),
        });

    const togglePresenceMutation = useMutation({
        mutationFn: (id: number) => registrationsApi.togglePresence(id),
        onMutate: async (id) => {
            // Cancel outgoing refetches
            await queryClient.cancelQueries({
                queryKey: ["admin-registrations"],
            });

            // Snapshot previous value
            const previousData = queryClient.getQueryData([
                "admin-registrations",
                selectedSeminarId,
                searchTerm,
                page,
            ]);

            // Optimistically update
            queryClient.setQueryData(
                ["admin-registrations", selectedSeminarId, searchTerm, page],
                (old: typeof registrationsData) => {
                    if (!old) return old;
                    return {
                        ...old,
                        data: old.data.map((reg: AdminRegistration) =>
                            reg.id === id
                                ? { ...reg, present: !reg.present }
                                : reg,
                        ),
                    };
                },
            );

            return { previousData };
        },
        onError: (_err, _id, context) => {
            // Rollback on error
            if (context?.previousData) {
                queryClient.setQueryData(
                    [
                        "admin-registrations",
                        selectedSeminarId,
                        searchTerm,
                        page,
                    ],
                    context.previousData,
                );
            }
            toast.error("Erro ao atualizar presenca");
        },
        onSuccess: (data) => {
            const isPresent = data.data.present;
            toast.success(
                isPresent ? "Presenca confirmada" : "Presenca removida",
            );
            analytics.event("admin_registration_toggle_presence", {
                registration_id: data.data.id,
                present: isPresent,
            });
        },
        onSettled: () => {
            queryClient.invalidateQueries({
                queryKey: ["admin-registrations"],
            });
        },
    });

    const registrations = registrationsData?.data ?? [];
    const meta = registrationsData?.meta;

    const handleSeminarChange = (seminar: SeminarOption | null) => {
        setSelectedSeminar(seminar);
        setPage(1);
    };

    const clearFilters = () => {
        setSelectedSeminar(null);
        clearSearch();
        setPage(1);
    };

    return (
        <div className="space-y-6">
            <PageTitle title="Inscrições" />
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Inscricoes
                    </h1>
                    <p className="text-muted-foreground">
                        Gerenciar inscricoes e presencas dos seminarios
                    </p>
                </div>
                <Button onClick={() => setIsAddModalOpen(true)}>
                    <UserPlus className="mr-2 h-4 w-4" />
                    Adicionar inscricoes
                </Button>
            </div>

            {/* Filters */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Filtros</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap items-end gap-4">
                        <div className="space-y-2 min-w-[280px]">
                            <Label htmlFor="seminar-filter">Seminario</Label>
                            <SeminarCombobox
                                id="seminar-filter"
                                value={selectedSeminar}
                                onChange={handleSeminarChange}
                            />
                        </div>
                        <div className="space-y-2 flex-1 min-w-[200px]">
                            <Label htmlFor="search">Buscar usuario</Label>
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
                        {(selectedSeminarId || searchTerm) && (
                            <Button variant="outline" onClick={clearFilters}>
                                Limpar filtros
                            </Button>
                        )}
                    </div>
                </CardContent>
            </Card>

            {/* Registrations Table */}
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle>Lista de Inscricoes</CardTitle>
                        {meta && (
                            <span className="text-sm text-muted-foreground">
                                {meta.total} inscricoes encontradas
                            </span>
                        )}
                    </div>
                </CardHeader>
                <CardContent>
                    {isLoadingRegistrations ? (
                        <div className="space-y-3">
                            {[...Array(10)].map((_, i) => (
                                <Skeleton key={i} className="h-12 w-full" />
                            ))}
                        </div>
                    ) : registrations.length === 0 ? (
                        <div className="text-center py-12">
                            <p className="text-muted-foreground">
                                Nenhuma inscricao encontrada
                            </p>
                            {(selectedSeminarId || searchTerm) && (
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
                                        <TableHead>
                                            <div className="flex items-center gap-2">
                                                <User className="h-4 w-4" />
                                                Usuario
                                            </div>
                                        </TableHead>
                                        <TableHead>
                                            <div className="flex items-center gap-2">
                                                <Mail className="h-4 w-4" />
                                                Email
                                            </div>
                                        </TableHead>
                                        <TableHead>Seminario</TableHead>
                                        <TableHead>
                                            <div className="flex items-center gap-2">
                                                <Calendar className="h-4 w-4" />
                                                Data Inscricao
                                            </div>
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Presente
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {registrations.map((registration) => (
                                        <TableRow key={registration.id}>
                                            <TableCell className="font-medium">
                                                {registration.user?.name ??
                                                    "Usuario removido"}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {registration.user?.email ??
                                                    "-"}
                                            </TableCell>
                                            <TableCell>
                                                <div
                                                    className="max-w-xs truncate"
                                                    title={
                                                        registration.seminar
                                                            ?.name
                                                    }
                                                >
                                                    {registration.seminar
                                                        ?.name ??
                                                        "Seminario removido"}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {formatDateTime(
                                                    registration.created_at,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <div className="flex items-center justify-center gap-2">
                                                    <Switch
                                                        checked={
                                                            registration.present
                                                        }
                                                        onCheckedChange={() =>
                                                            togglePresenceMutation.mutate(
                                                                registration.id,
                                                            )
                                                        }
                                                        disabled={
                                                            togglePresenceMutation.isPending
                                                        }
                                                    />
                                                    <Badge
                                                        variant={
                                                            registration.present
                                                                ? "success"
                                                                : "secondary"
                                                        }
                                                    >
                                                        {registration.present
                                                            ? "Sim"
                                                            : "Nao"}
                                                    </Badge>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            {meta && (
                                <Pagination
                                    currentPage={meta.current_page}
                                    lastPage={meta.last_page}
                                    from={meta.from ?? 0}
                                    to={meta.to ?? 0}
                                    total={meta.total}
                                    itemLabel="inscrições"
                                    onPageChange={setPage}
                                />
                            )}
                        </>
                    )}
                </CardContent>
            </Card>

            <AddRegistrationsModal
                open={isAddModalOpen}
                onClose={() => setIsAddModalOpen(false)}
                initialSeminar={selectedSeminar}
            />
        </div>
    );
}
