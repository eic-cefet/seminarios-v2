import { useState } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import {
    Search,
    ScrollText,
    User,
    Bot,
    Activity,
    FileDown,
} from "lucide-react";
import { toast } from "sonner";
import { PageTitle } from "@shared/components/PageTitle";
import { auditLogsApi } from "../../api/adminClient";
import { Button } from "../../components/ui/button";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "../../components/ui/card";
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

type DaysFilter = "7" | "30" | "60" | "90" | "365";
type EventTypeFilter = "all" | "manual" | "system";

const DAYS_OPTIONS: { value: DaysFilter; label: string }[] = [
    { value: "7", label: "7 dias" },
    { value: "30", label: "30 dias" },
    { value: "60", label: "60 dias" },
    { value: "90", label: "90 dias" },
    { value: "365", label: "365 dias" },
];

export default function AuditLogReport() {
    const [days, setDays] = useState<DaysFilter>("30");
    const [eventType, setEventType] = useState<EventTypeFilter>("all");
    const [eventName, setEventName] = useState("all");
    const [search, setSearch] = useState("");

    const { data: eventNamesData } = useQuery({
        queryKey: ["admin-audit-event-names"],
        queryFn: () => auditLogsApi.eventNames(),
        staleTime: 1000 * 60 * 10,
    });

    const { data: summaryData } = useQuery({
        queryKey: ["admin-audit-summary", days],
        queryFn: () =>
            auditLogsApi.summary({ days: Number.parseInt(days, 10) }),
    });

    const exportMutation = useMutation({
        mutationFn: () =>
            auditLogsApi.export({
                days: Number.parseInt(days, 10),
                event_type: eventType !== "all" ? eventType : undefined,
                event_name: eventName !== "all" ? eventName : undefined,
                search: search || undefined,
            }),
        onSuccess: (result) => {
            toast.success("Relatório gerado com sucesso!");
            window.open(result.url, "_blank");
        },
        onError: () => {
            toast.error("Erro ao exportar logs de auditoria.");
        },
    });

    const summary = summaryData?.data;
    const eventNames = eventNamesData?.data ?? [];

    const topEventsEntries = summary?.top_events
        ? Object.entries(summary.top_events).slice(0, 3)
        : [];

    return (
        <div className="space-y-6">
            <PageTitle title="Logs de Auditoria" />

            <div className="flex items-start justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Logs de Auditoria
                    </h1>
                    <p className="text-muted-foreground">
                        Exporte os registros de atividades do sistema para
                        análise.
                    </p>
                </div>
            </div>

            <div className="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="flex items-center gap-2 text-sm font-medium">
                            <Activity className="h-4 w-4 text-blue-500" />
                            Total de eventos
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {summary?.total ?? 0}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="flex items-center gap-2 text-sm font-medium">
                            <User className="h-4 w-4 text-green-500" />
                            Eventos manuais
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {summary?.manual_count ?? 0}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="flex items-center gap-2 text-sm font-medium">
                            <Bot className="h-4 w-4 text-purple-500" />
                            Eventos do sistema
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {summary?.system_count ?? 0}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="flex items-center gap-2 text-sm font-medium">
                            <ScrollText className="h-4 w-4 text-orange-500" />
                            Eventos mais frequentes
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {topEventsEntries.length > 0 ? (
                            <ul className="space-y-1">
                                {topEventsEntries.map(([name, count]) => (
                                    <li
                                        key={name}
                                        className="flex items-center justify-between text-sm"
                                    >
                                        <span className="truncate font-mono text-xs">
                                            {name}
                                        </span>
                                        <Badge
                                            variant="outline"
                                            className="ml-2 shrink-0"
                                        >
                                            {count}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <span className="text-sm text-muted-foreground">
                                -
                            </span>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Exportar relatório
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-[180px_180px_220px_minmax(0,1fr)]">
                        <div className="space-y-2">
                            <Label htmlFor="audit-days">Período</Label>
                            <Select
                                value={days}
                                onValueChange={(value: DaysFilter) =>
                                    setDays(value)
                                }
                            >
                                <SelectTrigger id="audit-days">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {DAYS_OPTIONS.map((opt) => (
                                        <SelectItem
                                            key={opt.value}
                                            value={opt.value}
                                        >
                                            {opt.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="audit-type">Tipo</Label>
                            <Select
                                value={eventType}
                                onValueChange={(value: EventTypeFilter) =>
                                    setEventType(value)
                                }
                            >
                                <SelectTrigger id="audit-type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem value="manual">
                                        Manual
                                    </SelectItem>
                                    <SelectItem value="system">
                                        Sistema
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="audit-event">Evento</Label>
                            <Select
                                value={eventName}
                                onValueChange={(value: string) =>
                                    setEventName(value)
                                }
                            >
                                <SelectTrigger id="audit-event">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    {eventNames.map((name) => (
                                        <SelectItem key={name} value={name}>
                                            {name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="audit-search">Buscar</Label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    id="audit-search"
                                    placeholder="Usuário, evento, origem, IP..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end">
                        <Button
                            onClick={() => exportMutation.mutate()}
                            disabled={exportMutation.isPending}
                        >
                            <FileDown className="mr-2 h-4 w-4" />
                            {/* v8 ignore start */}
                            {exportMutation.isPending
                                ? "Exportando..."
                                : "Exportar Excel"}
                            {/* v8 ignore stop */}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
