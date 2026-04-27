import { useQuery } from "@tanstack/react-query";
import {
    CalendarClock,
    Cpu,
    Database,
    HardDrive,
    Server,
    Settings2,
    Sparkles,
    Workflow,
} from "lucide-react";
import { PageTitle } from "@shared/components/PageTitle";
import { formatDateTime } from "@shared/lib/utils";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "../../components/ui/card";
import { Skeleton } from "../../components/ui/skeleton";
import { Badge } from "../../components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "../../components/ui/table";
import { systemInfoApi, type AdminSystemInfo } from "../../api/adminClient";

function formatBytes(bytes: number): string {
    if (bytes < 0) return "Sem limite";
    if (bytes === 0) return "0 B";
    const units = ["B", "KB", "MB", "GB", "TB"];
    const exp = Math.min(
        units.length - 1,
        Math.floor(Math.log(bytes) / Math.log(1024)),
    );
    const value = bytes / Math.pow(1024, exp);
    return `${value.toFixed(1)} ${units[exp]}`;
}

function Field({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex flex-col gap-1">
            <span className="text-xs uppercase tracking-wide text-muted-foreground">
                {label}
            </span>
            <span className="text-sm font-medium text-foreground break-all">
                {value}
            </span>
        </div>
    );
}

function InfoCard({
    title,
    icon: Icon,
    children,
}: {
    title: string;
    icon: React.ComponentType<{ className?: string }>;
    children: React.ReactNode;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                    <Icon className="h-4 w-4" />
                    {title}
                </CardTitle>
            </CardHeader>
            <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {children}
            </CardContent>
        </Card>
    );
}

export default function SystemInfo() {
    const { data, isLoading, isError } = useQuery({
        queryKey: ["admin", "system-info"],
        queryFn: () => systemInfoApi.get(),
        staleTime: 30_000,
    });

    if (isLoading) {
        return (
            <div className="space-y-6" role="status" aria-label="Carregando">
                <PageTitle title="Sistema" />
                <h1 className="text-2xl font-bold text-foreground">Sistema</h1>
                <Skeleton className="h-32 w-full" />
                <Skeleton className="h-32 w-full" />
                <Skeleton className="h-32 w-full" />
            </div>
        );
    }

    if (isError || !data) {
        return (
            <div className="space-y-4">
                <PageTitle title="Sistema" />
                <h1 className="text-2xl font-bold text-foreground">Sistema</h1>
                <p className="text-sm text-muted-foreground">
                    Não foi possível carregar as informações do sistema.
                </p>
            </div>
        );
    }

    const info: AdminSystemInfo = data.data;

    return (
        <div className="space-y-6">
            <PageTitle title="Sistema" />
            <h1 className="text-2xl font-bold text-foreground">Sistema</h1>

            <div className="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <InfoCard title="Runtime" icon={Cpu}>
                    <Field label="PHP" value={info.runtime.php_version} />
                    <Field
                        label="Laravel"
                        value={info.runtime.laravel_version}
                    />
                    <Field label="Ambiente" value={info.runtime.environment} />
                    <Field
                        label="Debug"
                        value={info.runtime.debug ? "Ativado" : "Desativado"}
                    />
                    <Field label="Fuso horário" value={info.runtime.timezone} />
                    <Field label="Locale" value={info.runtime.locale} />
                </InfoCard>

                <InfoCard title="Servidor" icon={Server}>
                    <Field label="SO" value={info.server.os_family} />
                    <Field
                        label="Versão do SO"
                        value={info.server.os_release}
                    />
                    <Field label="Host" value={info.server.hostname} />
                    <Field
                        label="Servidor Web"
                        value={info.server.server_software}
                    />
                    <Field label="SAPI" value={info.server.sapi} />
                    <Field
                        label="Arquitetura"
                        value={info.server.architecture}
                    />
                </InfoCard>

                <InfoCard title="Banco de dados" icon={Database}>
                    <Field label="Driver" value={info.database.driver} />
                    <Field label="Versão" value={info.database.version} />
                    <Field
                        label="Database"
                        value={info.database.database ?? "—"}
                    />
                    <Field label="Host" value={info.database.host ?? "—"} />
                </InfoCard>

                <InfoCard title="Cache & Filas" icon={Workflow}>
                    <Field label="Cache" value={info.drivers.cache} />
                    <Field label="Fila" value={info.drivers.queue} />
                    <Field label="Sessão" value={info.drivers.session} />
                    <Field label="E-mail" value={info.drivers.mail} />
                    <Field
                        label="Filesystem"
                        value={info.drivers.filesystem}
                    />
                </InfoCard>

                <InfoCard title="Memória" icon={Sparkles}>
                    <Field
                        label="Limite"
                        value={formatBytes(info.memory.limit_bytes)}
                    />
                    <Field
                        label="Em uso"
                        value={formatBytes(info.memory.current_bytes)}
                    />
                    <Field
                        label="Pico"
                        value={formatBytes(info.memory.peak_bytes)}
                    />
                </InfoCard>

                <InfoCard title="Armazenamento" icon={HardDrive}>
                    <Field
                        label="Total"
                        value={formatBytes(info.storage.total_bytes)}
                    />
                    <Field
                        label="Livre"
                        value={formatBytes(info.storage.free_bytes)}
                    />
                </InfoCard>

                <InfoCard title="Configuração PHP" icon={Settings2}>
                    <Field
                        label="max_execution_time"
                        value={`${info.php_config.max_execution_time}s`}
                    />
                    <Field
                        label="post_max_size"
                        value={info.php_config.post_max_size}
                    />
                    <Field
                        label="upload_max_filesize"
                        value={info.php_config.upload_max_filesize}
                    />
                    <Field
                        label="OPcache"
                        value={
                            info.php_config.opcache_enabled
                                ? "Ativado"
                                : "Desativado"
                        }
                    />
                </InfoCard>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <CalendarClock className="h-4 w-4" />
                        Agendador
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {info.scheduler.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Nenhuma rotina agendada.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Comando</TableHead>
                                        <TableHead>Cron</TableHead>
                                        <TableHead>Fuso</TableHead>
                                        <TableHead>
                                            Próxima execução
                                        </TableHead>
                                        <TableHead>Flags</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {info.scheduler.map((task, idx) => (
                                        <TableRow key={`${task.command}-${idx}`}>
                                            <TableCell className="font-mono text-xs break-all">
                                                {task.command}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs">
                                                {task.expression}
                                            </TableCell>
                                            <TableCell className="text-xs">
                                                {task.timezone}
                                            </TableCell>
                                            <TableCell className="text-xs">
                                                {task.next_run_at
                                                    ? formatDateTime(
                                                          task.next_run_at,
                                                      )
                                                    : "—"}
                                            </TableCell>
                                            <TableCell className="space-x-1">
                                                {task.without_overlapping ? (
                                                    <Badge variant="secondary">
                                                        sem sobreposição
                                                    </Badge>
                                                ) : null}
                                                {task.on_one_server ? (
                                                    <Badge variant="secondary">
                                                        servidor único
                                                    </Badge>
                                                ) : null}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Sparkles className="h-4 w-4" />
                        Extensões PHP carregadas
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="mb-3 text-sm text-muted-foreground">
                        {info.extensions.length} extensões
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {info.extensions.map((ext) => (
                            <Badge key={ext} variant="secondary">
                                {ext}
                            </Badge>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
