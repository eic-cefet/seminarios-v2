import { useState } from "react";
import { getSemester } from "@shared/lib/utils";
import { PageTitle } from "@shared/components/PageTitle";
import { StudentCombobox, type StudentOption } from "../../components/StudentCombobox";
import StudentDashboardView from "./StudentDashboardView";
import { Button } from "../../components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "../../components/ui/card";
import { Label } from "../../components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "../../components/ui/select";

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

interface AppliedFilters {
    studentId: number;
    semester: string;
}

export default function StudentTracking() {
    const { year, semester } = getSemester();
    const [selectedSemester, setSelectedSemester] = useState(`${year}.${semester}`);
    const [selectedStudent, setSelectedStudent] = useState<StudentOption | null>(null);
    const [applied, setApplied] = useState<AppliedFilters | null>(null);

    const semesters = generateSemesters();

    const handleSemesterChange = (value: string) => {
        setSelectedSemester(value);
        setSelectedStudent(null);
        setApplied(null);
    };

    const handleVerClick = () => {
        if (!selectedStudent) {
            return;
        }
        setApplied({ studentId: selectedStudent.id, semester: selectedSemester });
    };

    return (
        <>
            <PageTitle title="Acompanhamento do Aluno" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Acompanhamento do Aluno
                    </h1>
                    <p className="text-muted-foreground">
                        Consulte o painel de participação de um aluno em um período específico.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filtros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-[200px_1fr_auto] sm:items-end">
                            <div className="space-y-2">
                                <Label htmlFor="student-tracking-semester">Semestre</Label>
                                <Select
                                    value={selectedSemester}
                                    onValueChange={handleSemesterChange}
                                >
                                    <SelectTrigger id="student-tracking-semester">
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
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="student-tracking-student">Aluno</Label>
                                <StudentCombobox
                                    id="student-tracking-student"
                                    semester={selectedSemester}
                                    value={selectedStudent}
                                    onChange={setSelectedStudent}
                                />
                            </div>
                            <Button onClick={handleVerClick} disabled={!selectedStudent}>
                                Ver
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {applied ? (
                    <StudentDashboardView
                        studentId={applied.studentId}
                        semester={applied.semester}
                    />
                ) : (
                    <p className="text-muted-foreground text-center py-8">
                        Selecione um período e um aluno e clique em Ver.
                    </p>
                )}
            </div>
        </>
    );
}
