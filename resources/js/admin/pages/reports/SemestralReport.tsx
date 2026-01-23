import { useState } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import { PageTitle } from "@shared/components/PageTitle";
import { Button } from "../../components/ui/button";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "../../components/ui/card";
import { Label } from "../../components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "../../components/ui/select";
import { Checkbox } from "../../components/ui/checkbox";
import { MultiSelect } from "../../components/ui/multi-select";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "../../components/ui/table";
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from "../../components/ui/collapsible";
import { toast } from "sonner";
import { FileDown, Eye, ChevronDown, Users, Clock } from "lucide-react";

interface Presentation {
    name: string;
    date: string;
    type: string | null;
}

interface ReportUser {
    name: string;
    email: string;
    course: string;
    total_hours: number;
    presentations: Presentation[];
}

interface ReportSummary {
    total_users: number;
    total_hours: number;
    semester: string;
}

interface ReportData {
    data: ReportUser[];
    summary: ReportSummary;
}

// Generate semesters for the last 5 years
function generateSemesters() {
    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth() + 1; // 1-12
    const currentSemester = currentMonth <= 6 ? 1 : 2;

    const semesters: { value: string; label: string }[] = [];

    for (let year = currentYear; year >= currentYear - 5; year--) {
        const startSem = year === currentYear ? currentSemester : 2;
        for (let sem = startSem; sem >= 1; sem--) {
            semesters.push({
                value: `${year}.${sem}`,
                label: `${year}.${sem}`,
            });
        }
    }

    return semesters;
}

// Course situations
const COURSE_SITUATIONS = ["Cursando", "Trancado", "Concluído", "Outro"];

const API_BASE = app.API_URL + "/admin";

// Helper to get CSRF cookie
const getCsrfCookie = async () => {
    await fetch("/sanctum/csrf-cookie", {
        credentials: "same-origin",
    });
};

// Helper to get CSRF token from cookie
const getCsrfToken = () => {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : "";
};

const fetchSeminarTypes = async () => {
    const response = await fetch(`${API_BASE}/seminar-types`, {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
    });
    return response.json();
};

const fetchCourses = async () => {
    const response = await fetch(`${API_BASE}/reports/courses`, {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
    });
    return response.json();
};

export default function SemestralReport() {
    const [semester, setSemester] = useState<string>("");
    const [selectedCourses, setSelectedCourses] = useState<(string | number)[]>(
        [],
    );
    const [selectedTypes, setSelectedTypes] = useState<(string | number)[]>([]);
    const [selectedSituations, setSelectedSituations] = useState<string[]>([]);
    const [format, setFormat] = useState<"browser" | "excel">("browser");
    const [reportData, setReportData] = useState<ReportData | null>(null);
    const [expandedRows, setExpandedRows] = useState<Set<number>>(new Set());

    const { data: typesData } = useQuery({
        queryKey: ["admin-seminar-types"],
        queryFn: fetchSeminarTypes,
    });

    const { data: coursesData } = useQuery({
        queryKey: ["admin-report-courses"],
        queryFn: fetchCourses,
    });

    const seminarTypes = (typesData?.data ?? []).map((type: any) => ({
        value: type.id,
        label: type.name,
    }));

    const courses = coursesData?.data ?? [];
    const semesters = generateSemesters();

    const generateMutation = useMutation({
        mutationFn: async () => {
            await getCsrfCookie();

            const params = new URLSearchParams();
            params.set("semester", semester);
            if (selectedCourses.length > 0) {
                selectedCourses.forEach((c) =>
                    params.append("courses[]", String(c)),
                );
            }
            if (selectedTypes.length > 0) {
                selectedTypes.forEach((t) =>
                    params.append("types[]", String(t)),
                );
            }
            if (selectedSituations.length > 0) {
                selectedSituations.forEach((s) =>
                    params.append("situations[]", s),
                );
            }
            params.set("format", format);

            const response = await fetch(
                `${API_BASE}/reports/semestral?${params.toString()}`,
                {
                    headers: {
                        Accept: "application/json",
                        "X-XSRF-TOKEN": getCsrfToken(),
                    },
                    credentials: "same-origin",
                },
            );

            if (!response.ok) {
                throw new Error("Failed to generate report");
            }

            return response.json();
        },
        onSuccess: (data) => {
            if (format === "excel") {
                // For Excel, the backend will return a signed URL
                toast.success("Relatório gerado com sucesso!");
                window.open(data.url, "_blank");
            } else {
                toast.success("Relatório gerado!");
                setReportData(data);
                setExpandedRows(new Set());
            }
        },
        onError: () => {
            toast.error("Erro ao gerar relatório");
        },
    });

    const handleSituationToggle = (situation: string) => {
        setSelectedSituations((prev) =>
            prev.includes(situation)
                ? prev.filter((s) => s !== situation)
                : [...prev, situation],
        );
    };

    const toggleRow = (index: number) => {
        setExpandedRows((prev) => {
            const next = new Set(prev);
            if (next.has(index)) {
                next.delete(index);
            } else {
                next.add(index);
            }
            return next;
        });
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString("pt-BR", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!semester) {
            toast.error("Selecione um semestre");
            return;
        }

        generateMutation.mutate();
    };

    return (
        <>
            <PageTitle title="Relatório Semestral" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Relatório Semestral
                    </h1>
                    <p className="text-muted-foreground">
                        Gere relatórios de participação semestral dos usuários
                    </p>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Semester Selection */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Período</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="semester">Semestre *</Label>
                                    <Select
                                        value={semester}
                                        onValueChange={setSemester}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecione o semestre" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {semesters.map((sem) => (
                                                <SelectItem
                                                    key={sem.value}
                                                    value={sem.value}
                                                >
                                                    {sem.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Format Selection */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Formato</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Formato do relatório *</Label>
                                    <Select
                                        value={format}
                                        onValueChange={(v) =>
                                            setFormat(v as "browser" | "excel")
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="browser">
                                                Visualizar no navegador
                                            </SelectItem>
                                            <SelectItem value="excel">
                                                Baixar Excel
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Courses Filter */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Cursos (opcional)</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <MultiSelect
                                    options={courses}
                                    selected={selectedCourses}
                                    onChange={setSelectedCourses}
                                    placeholder="Selecione cursos..."
                                />
                            </CardContent>
                        </Card>

                        {/* Presentation Types Filter */}
                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    Tipos de Apresentação (opcional)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <MultiSelect
                                    options={seminarTypes}
                                    selected={selectedTypes}
                                    onChange={setSelectedTypes}
                                    placeholder="Selecione tipos..."
                                />
                            </CardContent>
                        </Card>

                        {/* Course Situations Filter */}
                        <Card className="lg:col-span-2">
                            <CardHeader>
                                <CardTitle>
                                    Situação do Curso (opcional)
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                {COURSE_SITUATIONS.map((situation) => (
                                    <div
                                        key={situation}
                                        className="flex items-center space-x-2"
                                    >
                                        <Checkbox
                                            id={`situation-${situation}`}
                                            checked={selectedSituations.includes(
                                                situation,
                                            )}
                                            onCheckedChange={() =>
                                                handleSituationToggle(situation)
                                            }
                                        />
                                        <label
                                            htmlFor={`situation-${situation}`}
                                            className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                        >
                                            {situation}
                                        </label>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Submit Button */}
                    <div className="flex justify-end mt-6">
                        <Button
                            type="submit"
                            disabled={generateMutation.isPending || !semester}
                            size="lg"
                        >
                            {generateMutation.isPending ? (
                                "Gerando..."
                            ) : format === "excel" ? (
                                <>
                                    <FileDown className="h-4 w-4 mr-2" />
                                    Baixar Excel
                                </>
                            ) : (
                                <>
                                    <Eye className="h-4 w-4 mr-2" />
                                    Visualizar Relatório
                                </>
                            )}
                        </Button>
                    </div>
                </form>

                {/* Report Results */}
                {reportData && (
                    <div className="space-y-6">
                        {/* Summary Cards */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="flex items-center gap-4">
                                        <div className="p-3 bg-primary/10 rounded-lg">
                                            <Users className="h-6 w-6 text-primary" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Total de Usuários
                                            </p>
                                            <p className="text-2xl font-bold">
                                                {reportData.summary.total_users}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="flex items-center gap-4">
                                        <div className="p-3 bg-primary/10 rounded-lg">
                                            <Clock className="h-6 w-6 text-primary" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Total de Horas
                                            </p>
                                            <p className="text-2xl font-bold">
                                                {reportData.summary.total_hours}
                                                h
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="flex items-center gap-4">
                                        <div className="p-3 bg-primary/10 rounded-lg">
                                            <Eye className="h-6 w-6 text-primary" />
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Semestre
                                            </p>
                                            <p className="text-2xl font-bold">
                                                {reportData.summary.semester}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Users Table */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Participantes</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {reportData.data.length === 0 ? (
                                    <p className="text-muted-foreground text-center py-8">
                                        Nenhum participante encontrado para os
                                        filtros selecionados.
                                    </p>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-10"></TableHead>
                                                <TableHead>Nome</TableHead>
                                                <TableHead>Email</TableHead>
                                                <TableHead>Curso</TableHead>
                                                <TableHead className="text-right">
                                                    Horas
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    Apresentações
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {reportData.data.map(
                                                (user, index) => (
                                                    <Collapsible
                                                        key={index}
                                                        open={expandedRows.has(
                                                            index,
                                                        )}
                                                        asChild
                                                    >
                                                        <>
                                                            <TableRow
                                                                className="cursor-pointer hover:bg-muted/50"
                                                                onClick={() =>
                                                                    toggleRow(
                                                                        index,
                                                                    )
                                                                }
                                                            >
                                                                <TableCell>
                                                                    <CollapsibleTrigger
                                                                        asChild
                                                                    >
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            className="p-0 h-6 w-6"
                                                                        >
                                                                            <ChevronDown
                                                                                className={`h-4 w-4 transition-transform ${
                                                                                    expandedRows.has(
                                                                                        index,
                                                                                    )
                                                                                        ? "rotate-180"
                                                                                        : ""
                                                                                }`}
                                                                            />
                                                                        </Button>
                                                                    </CollapsibleTrigger>
                                                                </TableCell>
                                                                <TableCell className="font-medium">
                                                                    {user.name}
                                                                </TableCell>
                                                                <TableCell>
                                                                    {user.email}
                                                                </TableCell>
                                                                <TableCell>
                                                                    {
                                                                        user.course
                                                                    }
                                                                </TableCell>
                                                                <TableCell className="text-right">
                                                                    {
                                                                        user.total_hours
                                                                    }
                                                                    h
                                                                </TableCell>
                                                                <TableCell className="text-right">
                                                                    {
                                                                        user
                                                                            .presentations
                                                                            .length
                                                                    }
                                                                </TableCell>
                                                            </TableRow>
                                                            <CollapsibleContent
                                                                asChild
                                                            >
                                                                <TableRow className="bg-muted/30">
                                                                    <TableCell
                                                                        colSpan={
                                                                            6
                                                                        }
                                                                        className="p-0"
                                                                    >
                                                                        <div className="px-8 py-4">
                                                                            <p className="text-sm font-medium mb-2">
                                                                                Apresentações
                                                                                assistidas:
                                                                            </p>
                                                                            <ul className="space-y-1">
                                                                                {user.presentations.map(
                                                                                    (
                                                                                        presentation,
                                                                                        pIndex,
                                                                                    ) => (
                                                                                        <li
                                                                                            key={
                                                                                                pIndex
                                                                                            }
                                                                                            className="text-sm text-muted-foreground flex gap-2"
                                                                                        >
                                                                                            <span className="text-foreground">
                                                                                                {
                                                                                                    presentation.name
                                                                                                }
                                                                                            </span>
                                                                                            <span>
                                                                                                -
                                                                                            </span>
                                                                                            <span>
                                                                                                {formatDate(
                                                                                                    presentation.date,
                                                                                                )}
                                                                                            </span>
                                                                                            {presentation.type && (
                                                                                                <>
                                                                                                    <span>
                                                                                                        -
                                                                                                    </span>
                                                                                                    <span className="text-primary">
                                                                                                        {
                                                                                                            presentation.type
                                                                                                        }
                                                                                                    </span>
                                                                                                </>
                                                                                            )}
                                                                                        </li>
                                                                                    ),
                                                                                )}
                                                                            </ul>
                                                                        </div>
                                                                    </TableCell>
                                                                </TableRow>
                                                            </CollapsibleContent>
                                                        </>
                                                    </Collapsible>
                                                ),
                                            )}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                )}
            </div>
        </>
    );
}
