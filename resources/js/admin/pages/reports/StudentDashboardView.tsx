import { useQuery } from "@tanstack/react-query";
import { Sparkles, Clock, CheckCircle2, XCircle, CalendarClock, Download } from "lucide-react";
import {
    ResponsiveContainer,
    BarChart,
    Bar,
    Cell,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
} from "recharts";
import ReactMarkdown from "react-markdown";
import rehypeSanitize from "rehype-sanitize";
import { formatDateTime, formatDurationMinutes } from "@shared/lib/utils";
import { studentsApi, AdminApiError, type StudentRegistration, type StudentCertificate } from "../../api/adminClient";
import { StatsCard } from "../Dashboard";
import { Card, CardContent, CardHeader, CardTitle } from "../../components/ui/card";
import { Badge } from "../../components/ui/badge";
import { Skeleton } from "../../components/ui/skeleton";
import { Button } from "../../components/ui/button";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "../../components/ui/tabs";
import { StudentGamificationPanel } from "../../components/students/StudentGamificationPanel";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "../../components/ui/table";

const STATUS_LABELS: Record<string, { label: string; variant: "default" | "secondary" | "destructive" }> = {
    attended: { label: "Compareceu", variant: "default" },
    missed: { label: "Faltou", variant: "destructive" },
    upcoming: { label: "A ocorrer", variant: "secondary" },
};

interface StudentDashboardViewProps {
    studentId: number;
    semester: string;
}

export default function StudentDashboardView({ studentId, semester }: StudentDashboardViewProps) {
    const {
        data,
        isLoading,
        error: dashboardError,
    } = useQuery({
        queryKey: ["admin-student-dashboard", studentId, semester],
        queryFn: () => studentsApi.dashboard(studentId, semester),
        retry: false,
    });

    const {
        data: aiSummaryData,
        error: aiSummaryError,
        isLoading: isAiSummaryLoading,
    } = useQuery({
        queryKey: ["admin-student-ai-summary", studentId, semester],
        queryFn: () => studentsApi.aiSummary(studentId, semester),
        staleTime: 1000 * 60 * 60 * 24,
        refetchOnWindowFocus: false,
        retry: false,
    });

    if (isLoading) {
        return (
            <div className="space-y-6">
                <Skeleton className="h-10 w-64" />
                <div className="grid gap-4 md:grid-cols-4">
                    {[...Array(4)].map((_, i) => (
                        <Skeleton key={i} className="h-24 w-full" />
                    ))}
                </div>
            </div>
        );
    }

    const dashboard = data?.data;
    if (!dashboard) {
        const isNotFound = dashboardError instanceof AdminApiError && dashboardError.status === 404;
        return (
            <p className="text-muted-foreground text-center py-8">
                {isNotFound
                    ? "Aluno não encontrado."
                    : "Não foi possível carregar os dados do aluno. Tente novamente mais tarde."}
            </p>
        );
    }

    const isAiNotConfigured =
        aiSummaryError instanceof AdminApiError && aiSummaryError.code === "ai_not_configured";

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-xl font-bold text-foreground">{dashboard.student.name}</h2>
                <p className="text-muted-foreground">
                    {dashboard.student.email} · {dashboard.student.course} · Semestre {dashboard.semester}
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Sparkles className="h-5 w-5 text-primary" />
                        Resumo da IA
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {isAiSummaryLoading ? (
                        <Skeleton className="h-16 w-full" />
                    ) : isAiNotConfigured ? (
                        <p className="text-sm text-muted-foreground">
                            Resumo por IA não configurado neste ambiente.
                        </p>
                    ) : aiSummaryError ? (
                        <p className="text-sm text-destructive">
                            Não foi possível gerar o resumo. Tente novamente mais tarde.
                        </p>
                    ) : (
                        <div className="html-content text-sm">
                            <ReactMarkdown rehypePlugins={[rehypeSanitize]}>
                                {/* v8 ignore next -- @preserve defensive guard: aiSummaryData is always set once loading/error are ruled out */ aiSummaryData?.data.summary ?? ""}
                            </ReactMarkdown>
                        </div>
                    )}
                </CardContent>
            </Card>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <StatsCard title="Compareceu" value={dashboard.totals.attended} icon={CheckCircle2} />
                <StatsCard title="Faltou" value={dashboard.totals.missed} icon={XCircle} />
                <StatsCard title="A ocorrer" value={dashboard.totals.upcoming} icon={CalendarClock} />
                <StatsCard
                    title="Horas assistidas"
                    value={formatDurationMinutes(dashboard.totals.hours_attended * 60)}
                    icon={Clock}
                />
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Presença Geral</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={220}>
                            <BarChart
                                data={[
                                    { name: "Compareceu", value: dashboard.totals.attended },
                                    { name: "Faltou", value: dashboard.totals.missed },
                                    { name: "A ocorrer", value: dashboard.totals.upcoming },
                                ]}
                            >
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                <XAxis dataKey="name" fontSize={12} />
                                <YAxis allowDecimals={false} fontSize={12} />
                                <Tooltip />
                                <Bar dataKey="value" radius={[4, 4, 0, 0]}>
                                    <Cell fill="hsl(var(--chart-2))" />
                                    <Cell fill="hsl(var(--destructive))" />
                                    <Cell fill="hsl(var(--chart-4))" />
                                </Bar>
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Apresentações por Tipo</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={220}>
                            <BarChart data={dashboard.by_type}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                <XAxis dataKey="type" fontSize={12} />
                                <YAxis allowDecimals={false} fontSize={12} />
                                <Tooltip />
                                <Bar dataKey="attended" name="Compareceu" fill="hsl(var(--chart-2))" radius={[4, 4, 0, 0]} />
                                <Bar dataKey="missed" name="Faltou" fill="hsl(var(--destructive))" radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Horas por Tipo</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={220}>
                            <BarChart data={dashboard.by_type}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                <XAxis dataKey="type" fontSize={12} />
                                <YAxis allowDecimals={false} fontSize={12} />
                                <Tooltip />
                                <Bar dataKey="hours" name="Horas" fill="hsl(var(--chart-1))" radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
            </div>

            <Tabs defaultValue="presentations">
                <TabsList>
                    <TabsTrigger value="presentations">Apresentações</TabsTrigger>
                    <TabsTrigger value="certificates">Certificados</TabsTrigger>
                    <TabsTrigger value="achievements">Conquistas</TabsTrigger>
                </TabsList>

                <TabsContent value="presentations">
                    <Card>
                        <CardContent className="p-0">
                            {dashboard.registrations.length === 0 ? (
                                <p className="text-muted-foreground text-center py-8">
                                    Nenhuma apresentação neste semestre.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Apresentação</TableHead>
                                            <TableHead>Tipo</TableHead>
                                            <TableHead>Data</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {dashboard.registrations.map((registration: StudentRegistration) => (
                                            <TableRow key={registration.id}>
                                                <TableCell className="font-medium">
                                                    {registration.seminar.name}
                                                </TableCell>
                                                <TableCell>
                                                    {registration.seminar.seminar_type ?? "—"}
                                                </TableCell>
                                                <TableCell>
                                                    {formatDateTime(registration.seminar.scheduled_at)}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={STATUS_LABELS[registration.status].variant}>
                                                        {STATUS_LABELS[registration.status].label}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="certificates">
                    <Card>
                        <CardContent className="p-0">
                            {dashboard.certificates.length === 0 ? (
                                <p className="text-muted-foreground text-center py-8">
                                    Nenhum certificado emitido neste semestre.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Apresentação</TableHead>
                                            <TableHead className="text-right">Ação</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {dashboard.certificates.map((certificate: StudentCertificate) => (
                                            <TableRow key={certificate.id}>
                                                <TableCell className="font-medium">
                                                    {certificate.seminar_name}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <a
                                                            href={certificate.download_url}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                        >
                                                            <Download className="h-4 w-4 mr-2" />
                                                            Baixar
                                                        </a>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="achievements">
                    <StudentGamificationPanel userId={studentId} />
                </TabsContent>
            </Tabs>
        </div>
    );
}
