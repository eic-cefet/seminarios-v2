import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { useNavigate } from "react-router-dom";
import { getSemester } from "@shared/lib/utils";
import { PageTitle } from "@shared/components/PageTitle";
import { Pagination } from "@shared/components/Pagination";
import { useDebouncedSearch } from "@shared/hooks/useDebouncedSearch";
import { ROUTES } from "@shared/config/routes";
import { studentsApi } from "../../api/adminClient";
import { Card, CardContent, CardHeader, CardTitle } from "../../components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "../../components/ui/select";
import { Input } from "../../components/ui/input";
import { Badge } from "../../components/ui/badge";
import { Skeleton } from "../../components/ui/skeleton";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "../../components/ui/table";

function generateSemesters() {
    const { year: currentYear, semester: currentSemester } = getSemester();
    const semesters: { value: string; label: string }[] = [];

    for (let year = currentYear; year >= currentYear - 5; year--) {
        const startSem = year === currentYear ? currentSemester : 2;
        for (let sem = startSem; sem >= 1; sem--) {
            semesters.push({ value: `${year}.${sem}`, label: `${year}.${sem}` });
        }
    }

    return semesters;
}

export default function StudentList() {
    const navigate = useNavigate();
    const { year, semester } = getSemester();
    const [selectedSemester, setSelectedSemester] = useState(`${year}.${semester}`);
    const [page, setPage] = useState(1);

    const { inputValue: searchInput, debouncedValue: searchTerm, setInputValue: setSearchInput } =
        useDebouncedSearch({ onDebouncedChange: () => setPage(1) });

    const semesters = generateSemesters();

    const { data, isLoading } = useQuery({
        queryKey: ["admin-students", { semester: selectedSemester, search: searchTerm, page }],
        queryFn: () =>
            studentsApi.list({
                semester: selectedSemester,
                search: searchTerm || undefined,
                page,
            }),
    });

    return (
        <>
            <PageTitle title="Alunos" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">Alunos</h1>
                    <p className="text-muted-foreground">
                        Painel de acompanhamento dos alunos por semestre
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4 sm:flex-row">
                        <Select
                            value={selectedSemester}
                            onValueChange={(value) => {
                                setSelectedSemester(value);
                                setPage(1);
                            }}
                        >
                            <SelectTrigger className="sm:w-48">
                                <SelectValue placeholder="Semestre" />
                            </SelectTrigger>
                            <SelectContent>
                                {semesters.map((sem) => (
                                    <SelectItem key={sem.value} value={sem.value}>
                                        {sem.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Input
                            placeholder="Buscar por nome ou e-mail..."
                            value={searchInput}
                            onChange={(e) => setSearchInput(e.target.value)}
                            className="sm:max-w-sm"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        {isLoading ? (
                            <div className="space-y-3 p-6">
                                {[...Array(5)].map((_, i) => (
                                    <Skeleton key={i} className="h-12 w-full" />
                                ))}
                            </div>
                        ) : data?.data.length === 0 ? (
                            <p className="text-muted-foreground text-center py-8">
                                Nenhum aluno encontrado para o semestre selecionado.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nome</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Curso</TableHead>
                                        <TableHead>Situação</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data?.data.map((student) => (
                                        <TableRow
                                            key={student.id}
                                            className="cursor-pointer hover:bg-muted/50"
                                            onClick={() =>
                                                navigate(
                                                    `${ROUTES.ADMIN.STUDENT_PROFILE(student.id)}?semester=${selectedSemester}`,
                                                )
                                            }
                                        >
                                            <TableCell className="font-medium">{student.name}</TableCell>
                                            <TableCell>{student.email}</TableCell>
                                            <TableCell>{student.course}</TableCell>
                                            <TableCell>
                                                {student.course_situation && (
                                                    <Badge variant="secondary">
                                                        {student.course_situation === "studying"
                                                            ? "Cursando"
                                                            : "Concluído"}
                                                    </Badge>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                        {data?.meta && (
                            <Pagination
                                currentPage={data.meta.current_page}
                                lastPage={data.meta.last_page}
                                total={data.meta.total}
                                onPageChange={setPage}
                                itemLabel="alunos"
                            />
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
