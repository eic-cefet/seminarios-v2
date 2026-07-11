import { useSearchParams, useParams } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { Sparkles, Clock, CheckCircle2, XCircle, CalendarClock, Download } from "lucide-react";
import { getSemester, formatDateTime, formatDurationMinutes } from "@shared/lib/utils";
import { PageTitle } from "@shared/components/PageTitle";
import { studentsApi, AdminApiError, type StudentRegistration, type StudentCertificate } from "../../api/adminClient";
import { StatsCard } from "../Dashboard";
import { Card, CardContent, CardHeader, CardTitle } from "../../components/ui/card";
import { Badge } from "../../components/ui/badge";
import { Skeleton } from "../../components/ui/skeleton";
import { Button } from "../../components/ui/button";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "../../components/ui/tabs";
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

export default function StudentProfile() {
    const { userId } = useParams<{ userId: string }>();
    const [searchParams] = useSearchParams();
    const { year, semester } = getSemester();
    const selectedSemester = searchParams.get("semester") ?? `${year}.${semester}`;
    const studentId = Number(userId);

    const { data, isLoading } = useQuery({
        queryKey: ["admin-student-dashboard", studentId, selectedSemester],
        queryFn: () => studentsApi.dashboard(studentId, selectedSemester),
    });

    const {
        data: aiSummaryData,
        error: aiSummaryError,
        isLoading: isAiSummaryLoading,
    } = useQuery({
        queryKey: ["admin-student-ai-summary", studentId, selectedSemester],
        queryFn: () => studentsApi.aiSummary(studentId, selectedSemester),
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
        return <p className="text-muted-foreground text-center py-8">Aluno não encontrado.</p>;
    }

    const isAiNotConfigured =
        aiSummaryError instanceof AdminApiError && aiSummaryError.code === "ai_not_configured";

    return (
        <>
            <PageTitle title={`Aluno - ${dashboard.student.name}`} />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">{dashboard.student.name}</h1>
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
                            <p className="text-sm text-foreground">{aiSummaryData?.data.summary}</p>
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

                <Tabs defaultValue="presentations">
                    <TabsList>
                        <TabsTrigger value="presentations">Apresentações</TabsTrigger>
                        <TabsTrigger value="certificates">Certificados</TabsTrigger>
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
                </Tabs>
            </div>
        </>
    );
}
