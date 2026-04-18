import React, { useState } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import {
    getSemester,
    formatDateTime as formatDateTimeUtil,
    formatDurationMinutes,
} from "@shared/lib/date";
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
import { toast } from "sonner";
import { FileDown, Eye, ChevronDown, Users, Clock } from "lucide-react";
import {
    dropdownApi,
    reportsApi,
    type SeminarTypeDropdownItem,
    type SemestralReportBrowserResponse,
} from "../../api/adminClient";

// Generate semesters for the last 5 years
function generateSemesters() {
    const { year: currentYear, semester: currentSemester } = getSemester();

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

const COURSE_SITUATIONS = ["Cursando", "Trancado", "Concluído", "Outro"];

function formatHours(hours: number): string {
    return hours.toFixed(2).replace(/\.?0+$/, "");
}

export default function SemestralReport() {
    const [semester, setSemester] = useState<string>("");
    const [selectedCourses, setSelectedCourses] = useState<(string | number)[]>(
        [],
    );
    const [selectedTypes, setSelectedTypes] = useState<(string | number)[]>([]);
    const [selectedSituations, setSelectedSituations] = useState<string[]>([]);
    const [format, setFormat] = useState<"browser" | "excel">("browser");
    const [reportData, setReportData] = useState<SemestralReportBrowserResponse | null>(
        null,
    );
    const [expandedRows, setExpandedRows] = useState<Set<number>>(new Set());

    const { data: typesData } = useQuery({
        queryKey: ["admin-seminar-types"],
        queryFn: dropdownApi.seminarTypes,
    });

    const { data: coursesData } = useQuery({
        queryKey: ["admin-report-courses"],
        queryFn: dropdownApi.courses,
    });

    const seminarTypes = (
        (typesData?.data ?? []) as SeminarTypeDropdownItem[]
    ).map((type) => ({
        value: type.id,
        label: type.name,
    }));

    const courses = coursesData?.data ?? [];
    const semesters = generateSemesters();

    const generateMutation = useMutation({
        mutationFn: () =>
            reportsApi.semestral({
                semester,
                courses: selectedCourses,
                types: selectedTypes,
                situations: selectedSituations,
                format,
            }),
        onSuccess: (data) => {
            if ("message" in data) {
                toast.success(data.message);
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
                                                Receber por e-mail (Excel)
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
                                format === "excel" ? "Enviando..." : "Gerando..."
                            ) : format === "excel" ? (
                                <>
                                    <FileDown className="h-4 w-4 mr-2" />
                                    Enviar por e-mail
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
                                                {formatHours(
                                                    reportData.summary.total_hours,
                                                )}
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
                                                    <React.Fragment
                                                        key={index}
                                                    >
                                                        <TableRow
                                                            className="cursor-pointer hover:bg-muted/50"
                                                            onClick={() =>
                                                                toggleRow(
                                                                    index,
                                                                )
                                                            }
                                                        >
                                                            <TableCell>
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
                                                            </TableCell>
                                                            <TableCell className="font-medium">
                                                                {user.name}
                                                            </TableCell>
                                                            <TableCell>
                                                                {user.email}
                                                            </TableCell>
                                                            <TableCell>
                                                                {user.course}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {formatHours(
                                                                    user.total_hours,
                                                                )}
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
                                                        {expandedRows.has(
                                                            index,
                                                        ) && (
                                                            <TableRow className="bg-muted/30">
                                                                <TableCell
                                                                    colSpan={6}
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
                                                                                            {formatDateTimeUtil(
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
                                                                                        {presentation.duration_minutes ? (
                                                                                            <>
                                                                                                <span>
                                                                                                    -
                                                                                                </span>
                                                                                                <span>
                                                                                                    {formatDurationMinutes(
                                                                                                        presentation.duration_minutes,
                                                                                                    )}
                                                                                                </span>
                                                                                            </>
                                                                                        ) : null}
                                                                                    </li>
                                                                                ),
                                                                            )}
                                                                        </ul>
                                                                    </div>
                                                                </TableCell>
                                                            </TableRow>
                                                        )}
                                                    </React.Fragment>
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
