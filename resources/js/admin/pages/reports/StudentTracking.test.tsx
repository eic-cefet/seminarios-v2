import { render, screen, waitFor, userEvent, within } from "@/test/test-utils";
import { getSemester } from "@shared/lib/utils";

vi.mock("../../api/adminClient", async (importOriginal) => {
    const actual = await importOriginal<typeof import("../../api/adminClient")>();
    return {
        ...actual,
        studentsApi: { list: vi.fn(), dashboard: vi.fn(), aiSummary: vi.fn() },
    };
});

import StudentTracking from "./StudentTracking";
import { studentsApi } from "../../api/adminClient";

// Polyfill pointer capture methods for Radix Select in jsdom
if (!Element.prototype.hasPointerCapture) {
    Element.prototype.hasPointerCapture = vi.fn().mockReturnValue(false);
}
if (!Element.prototype.setPointerCapture) {
    Element.prototype.setPointerCapture = vi.fn();
}
if (!Element.prototype.releasePointerCapture) {
    Element.prototype.releasePointerCapture = vi.fn();
}

const makePage = (items: { id: number; name: string; email: string }[]) => ({
    data: items,
    meta: {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: items.length,
        from: items.length ? 1 : 0,
        to: items.length,
    },
    links: { first: "", last: "", prev: null, next: null },
});

const student = { id: 1, name: "Maria Estudante", email: "maria@example.com" };

const dashboardResponse = {
    data: {
        student: {
            id: 1,
            name: "Maria Estudante",
            email: "maria@example.com",
            course: "Engenharia",
            course_situation: "studying" as const,
        },
        semester: "2026.2",
        totals: { attended: 1, missed: 0, upcoming: 0, hours_attended: 1 },
        by_type: [{ type: "Palestra", attended: 1, missed: 0, hours: 1 }],
        registrations: [],
        certificates: [],
    },
};

async function selectStudent() {
    const user = userEvent.setup();
    await user.click(screen.getByRole("combobox", { name: "Aluno" }));
    await user.click(await screen.findByRole("option", { name: /Maria Estudante/ }));
    return user;
}

describe("StudentTracking", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(studentsApi.list).mockResolvedValue(makePage([student]) as any);
        vi.mocked(studentsApi.dashboard).mockResolvedValue(dashboardResponse);
        vi.mocked(studentsApi.aiSummary).mockResolvedValue({ data: { summary: "Resumo." } });
    });

    it("renders the filters card with semester select, student combobox and a disabled Ver button", () => {
        render(<StudentTracking />);

        expect(screen.getByText("Filtros")).toBeInTheDocument();
        expect(screen.getByRole("combobox", { name: "Semestre" })).toBeInTheDocument();
        expect(screen.getByRole("combobox", { name: "Aluno" })).toBeInTheDocument();
        expect(screen.getByRole("button", { name: "Ver" })).toBeDisabled();
        expect(
            screen.getByText("Selecione um período e um aluno e clique em Ver."),
        ).toBeInTheDocument();
    });

    it("defaults the semester select to the current semester", () => {
        render(<StudentTracking />);

        const { year, semester } = getSemester();
        const trigger = screen.getByRole("combobox", { name: "Semestre" });
        expect(within(trigger).getByText(`${year}.${semester}`)).toBeInTheDocument();
    });

    it("enables Ver only after a student is picked", async () => {
        render(<StudentTracking />);

        expect(screen.getByRole("button", { name: "Ver" })).toBeDisabled();

        await selectStudent();

        expect(screen.getByRole("button", { name: "Ver" })).toBeEnabled();
    });

    it("renders the dashboard inline after picking a student and clicking Ver", async () => {
        const user = userEvent.setup();
        render(<StudentTracking />);

        await user.click(screen.getByRole("combobox", { name: "Aluno" }));
        await user.click(await screen.findByRole("option", { name: /Maria Estudante/ }));
        await user.click(screen.getByRole("button", { name: "Ver" }));

        await waitFor(() => {
            expect(
                screen.getByRole("heading", { level: 2, name: "Maria Estudante" }),
            ).toBeInTheDocument();
        });

        const { year, semester } = getSemester();
        expect(studentsApi.dashboard).toHaveBeenCalledWith(1, `${year}.${semester}`);
        expect(
            screen.queryByText("Selecione um período e um aluno e clique em Ver."),
        ).not.toBeInTheDocument();
    });

    it("clears the selection and hides the dashboard when the semester changes", async () => {
        const user = userEvent.setup();
        render(<StudentTracking />);

        await user.click(screen.getByRole("combobox", { name: "Aluno" }));
        await user.click(await screen.findByRole("option", { name: /Maria Estudante/ }));
        await user.click(screen.getByRole("button", { name: "Ver" }));

        await waitFor(() => {
            expect(
                screen.getByRole("heading", { level: 2, name: "Maria Estudante" }),
            ).toBeInTheDocument();
        });

        const { year, semester } = getSemester();
        const currentSemesterLabel = `${year}.${semester}`;

        await user.click(screen.getByRole("combobox", { name: "Semestre" }));
        const options = await screen.findAllByRole("option");
        const otherSemesterOption = options.find(
            (option) => option.textContent !== currentSemesterLabel,
        );
        expect(otherSemesterOption).toBeDefined();
        await user.click(otherSemesterOption!);

        await waitFor(() => {
            expect(
                screen.queryByRole("heading", { level: 2, name: "Maria Estudante" }),
            ).not.toBeInTheDocument();
        });
        expect(
            screen.getByText("Selecione um período e um aluno e clique em Ver."),
        ).toBeInTheDocument();
        expect(screen.getByText("Selecionar aluno")).toBeInTheDocument();
        expect(screen.getByRole("button", { name: "Ver" })).toBeDisabled();
    });
});
