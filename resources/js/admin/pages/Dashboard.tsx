import { useQuery } from "@tanstack/react-query";
import {
    Users,
    Presentation,
    ClipboardList,
    BookOpen,
    Calendar,
    Star,
    AlertTriangle,
    UserPlus,
} from "lucide-react";
import { dashboardApi } from "../api/adminClient";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "../components/ui/card";
import { Badge } from "../components/ui/badge";
import { Skeleton } from "../components/ui/skeleton";
import { formatDateTime } from "@shared/lib/utils";
import { PageTitle } from "@shared/components/PageTitle";

function StatsCard({
    title,
    value,
    icon: Icon,
    description,
}: {
    title: string;
    value: number | string;
    icon: React.ComponentType<{ className?: string }>;
    description?: string;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
                {description && (
                    <p className="text-xs text-muted-foreground">
                        {description}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

function DashboardSkeleton() {
    return (
        <div className="space-y-6">
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {[...Array(4)].map((_, i) => (
                    <Card key={i}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <Skeleton className="h-4 w-20" />
                            <Skeleton className="h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <Skeleton className="h-8 w-16" />
                        </CardContent>
                    </Card>
                ))}
            </div>
            <div className="grid gap-6 lg:grid-cols-2">
                {[...Array(4)].map((_, i) => (
                    <Card key={i}>
                        <CardHeader>
                            <Skeleton className="h-5 w-32" />
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {[...Array(3)].map((_, j) => (
                                    <Skeleton key={j} className="h-12 w-full" />
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}

export default function Dashboard() {
    const { data, isLoading, error } = useQuery({
        queryKey: ["dashboard-stats"],
        queryFn: () => dashboardApi.stats(),
    });

    if (isLoading) {
        return (
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Dashboard
                    </h1>
                    <p className="text-muted-foreground">
                        Visao geral do sistema de seminarios
                    </p>
                </div>
                <DashboardSkeleton />
            </div>
        );
    }

    if (error) {
        return (
            <div className="flex h-96 items-center justify-center">
                <div className="text-center">
                    <p className="text-muted-foreground">
                        Erro ao carregar estatisticas
                    </p>
                </div>
            </div>
        );
    }

    const stats = data?.data;

    return (
        <div className="space-y-6">
            <PageTitle title="Dashboard" />
            <div>
                <h1 className="text-2xl font-bold text-foreground">
                    Dashboard
                </h1>
                <p className="text-muted-foreground">
                    Visao geral do sistema de seminarios
                </p>
            </div>

            {/* Stats Cards */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Usuarios"
                    value={stats?.counts.users ?? 0}
                    icon={Users}
                />
                <StatsCard
                    title="Seminarios"
                    value={stats?.counts.seminars ?? 0}
                    icon={Presentation}
                />
                <StatsCard
                    title="Inscricoes"
                    value={stats?.counts.registrations ?? 0}
                    icon={ClipboardList}
                />
                <StatsCard
                    title="Tópicos"
                    value={stats?.counts.subjects ?? 0}
                    icon={BookOpen}
                />
            </div>

            {/* Two Column Layout */}
            <div className="grid gap-6 lg:grid-cols-2">
                {/* Upcoming Seminars */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Calendar className="h-5 w-5" />
                            Proximas Apresentacoes
                        </CardTitle>
                        <CardDescription>Seminarios agendados</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {stats?.upcomingSeminars &&
                        stats.upcomingSeminars.length > 0 ? (
                            <div className="space-y-3">
                                {stats.upcomingSeminars.map((seminar) => (
                                    <div
                                        key={seminar.id}
                                        className="flex items-center justify-between rounded-lg border border-border p-3"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-medium text-foreground">
                                                {seminar.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {seminar.scheduled_at &&
                                                    formatDateTime(
                                                        seminar.scheduled_at,
                                                    )}
                                            </p>
                                        </div>
                                        {seminar.seminar_type && (
                                            <Badge
                                                variant="secondary"
                                                className="ml-2"
                                            >
                                                {seminar.seminar_type.name}
                                            </Badge>
                                        )}
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-center text-muted-foreground py-4">
                                Nenhum seminario agendado
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Near Capacity */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-yellow-500" />
                            Perto da Capacidade
                        </CardTitle>
                        <CardDescription>
                            Seminarios com 80%+ de inscricoes
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {stats?.nearCapacity &&
                        stats.nearCapacity.length > 0 ? (
                            <div className="space-y-3">
                                {stats.nearCapacity.map((seminar) => (
                                    <div
                                        key={seminar.id}
                                        className="flex items-center justify-between rounded-lg border border-yellow-500/30 bg-yellow-500/10 p-3"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-medium text-foreground">
                                                {seminar.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {seminar.location?.name}
                                            </p>
                                        </div>
                                        <Badge variant="warning">
                                            {seminar.registrations_count}/
                                            {seminar.location?.max_vacancies}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-center text-muted-foreground py-4">
                                Nenhum seminario perto da capacidade
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Latest Ratings */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Star className="h-5 w-5 text-yellow-500" />
                            Ultimas Avaliacoes
                        </CardTitle>
                        <CardDescription>
                            Avaliacoes recentes de seminarios
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {stats?.latestRatings &&
                        stats.latestRatings.length > 0 ? (
                            <div className="space-y-3">
                                {stats.latestRatings.map((rating) => (
                                    <div
                                        key={rating.id}
                                        className="flex items-start gap-3 rounded-lg border border-border p-3"
                                    >
                                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-500/20">
                                            <span className="font-bold text-yellow-500">
                                                {rating.score}
                                            </span>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-medium text-foreground">
                                                {rating.seminar?.name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                por {rating.user?.name}
                                            </p>
                                            {rating.comment && (
                                                <p className="mt-1 text-sm text-muted-foreground line-clamp-2">
                                                    {rating.comment}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-center text-muted-foreground py-4">
                                Nenhuma avaliacao ainda
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Recent Registrations */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <UserPlus className="h-5 w-5" />
                            Inscricoes Recentes
                        </CardTitle>
                        <CardDescription>
                            Ultimas inscricoes em seminarios
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {stats?.latestRegistrations &&
                        stats.latestRegistrations.length > 0 ? (
                            <div className="space-y-3">
                                {stats.latestRegistrations.map(
                                    (registration) => (
                                        <div
                                            key={registration.id}
                                            className="flex items-center justify-between rounded-lg border border-border p-3"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate font-medium text-foreground">
                                                    {registration.user?.name}
                                                </p>
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {registration.seminar?.name}
                                                </p>
                                            </div>
                                            <span className="text-xs text-muted-foreground">
                                                {formatDateTime(
                                                    registration.created_at,
                                                )}
                                            </span>
                                        </div>
                                    ),
                                )}
                            </div>
                        ) : (
                            <p className="text-center text-muted-foreground py-4">
                                Nenhuma inscricao ainda
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
