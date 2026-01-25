import { useState, useRef, useEffect } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Search, Calendar, User, Mail } from "lucide-react";
import { toast } from "sonner";
import { debounce } from "lodash";
import { analytics } from "@shared/lib/analytics";
import {
    registrationsApi,
    dashboardApi,
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "../../components/ui/select";
import { Badge } from "../../components/ui/badge";
import { Button } from "../../components/ui/button";
import { PageTitle } from "@shared/components/PageTitle";
import { formatDateTime } from "@shared/lib/utils";

export default function RegistrationList() {
    const queryClient = useQueryClient();
    const [selectedSeminarId, setSelectedSeminarId] = useState<string>("");
    const [searchInput, setSearchInput] = useState("");
    const [searchTerm, setSearchTerm] = useState("");
    const [page, setPage] = useState(1);

    // Fetch seminars for dropdown
    const { data: seminarsData, isLoading: isLoadingSeminars } = useQuery({
        queryKey: ["admin-seminars-list"],
        queryFn: () => dashboardApi.seminars(),
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
                    seminar_id: selectedSeminarId
                        ? parseInt(selectedSeminarId, 10)
                        : undefined,
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

    const seminars = seminarsData?.data ?? [];
    const registrations = registrationsData?.data ?? [];
    const meta = registrationsData?.meta;

    // Sort seminars by scheduled_at DESC for dropdown
    const sortedSeminars = [...seminars].sort(
        (a, b) =>
            new Date(b.scheduled_at).getTime() -
            new Date(a.scheduled_at).getTime(),
    );

    const handleSeminarChange = (value: string) => {
        setSelectedSeminarId(value === "all" ? "" : value);
        setPage(1);
    };

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

    const clearFilters = () => {
        setSelectedSeminarId("");
        setSearchInput("");
        setSearchTerm("");
        setPage(1);
    };

    return (
        <div className="space-y-6">
            <PageTitle title="Inscrições" />
            <div>
                <h1 className="text-2xl font-bold text-foreground">
                    Inscricoes
                </h1>
                <p className="text-muted-foreground">
                    Gerenciar inscricoes e presencas dos seminarios
                </p>
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
                            <Select
                                value={selectedSeminarId || "all"}
                                onValueChange={handleSeminarChange}
                            >
                                <SelectTrigger id="seminar-filter">
                                    <SelectValue placeholder="Todos os seminarios" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Todos os seminarios
                                    </SelectItem>
                                    {isLoadingSeminars ? (
                                        <SelectItem value="loading" disabled>
                                            Carregando...
                                        </SelectItem>
                                    ) : (
                                        sortedSeminars.map((seminar) => (
                                            <SelectItem
                                                key={seminar.id}
                                                value={seminar.id.toString()}
                                            >
                                                {seminar.name} -{" "}
                                                {formatDateTime(
                                                    seminar.scheduled_at,
                                                )}
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
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
                                        handleSearch(e.target.value)
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

                            {/* Pagination */}
                            {meta && meta.last_page > 1 && (
                                <div className="flex items-center justify-between mt-4 pt-4 border-t border-border">
                                    <div className="text-sm text-muted-foreground">
                                        Mostrando {meta.from} a {meta.to} de{" "}
                                        {meta.total} inscricoes
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
        </div>
    );
}
