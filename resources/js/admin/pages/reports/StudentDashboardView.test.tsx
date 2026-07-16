import { describe, it, expect, vi, beforeEach } from "vitest";
import { QueryClient } from "@tanstack/react-query";
import type { GamificationProfile } from "@shared/types";
import { render, screen, waitFor, userEvent } from "@/test/test-utils";
import StudentDashboardView from "./StudentDashboardView";
import { studentsApi, AdminApiError } from "../../api/adminClient";

vi.mock("../../api/adminClient", async (importOriginal) => {
    const actual = await importOriginal<typeof import("../../api/adminClient")>();
    return {
        ...actual,
        studentsApi: { dashboard: vi.fn(), aiSummary: vi.fn(), gamification: vi.fn() },
    };
});

const baseDashboard = {
    data: {
        student: { id: 1, name: "Maria Estudante", email: "maria@example.com", course: "Engenharia", course_situation: "studying" as const },
        semester: "2026.1",
        totals: { attended: 2, missed: 1, upcoming: 1, hours_attended: 3 },
        by_type: [{ type: "Palestra", attended: 2, missed: 1, hours: 3 }],
        registrations: [
            {
                id: 1,
                present: true,
                status: "attended" as const,
                certificate_code: "abc-123",
                seminar: { id: 1, name: "Seminário X", scheduled_at: "2026-03-01T13:00:00.000Z", duration_minutes: 60, seminar_type: "Palestra" },
            },
        ],
        certificates: [
            { id: 1, certificate_code: "abc-123", seminar_name: "Seminário X", download_url: "https://example.test/certificado/abc-123" },
        ],
    },
};

const gamificationProfile: GamificationProfile = {
    progress: {
        total_xp: 650,
        level: 4,
        rank: "Curioso",
        current_level_xp: 600,
        next_level_xp: 1000,
        progress_percent: 12,
    },
    summary: { earned_badges: 1, total_badges: 30 },
    categories: [
        {
            key: "participacao",
            label: "Participação",
            badges: [
                {
                    key: "first_presence",
                    name: "Primeiro Passo",
                    description: "Participe de uma apresentação.",
                    category: "Participação",
                    tier: "bronze",
                    icon: "Footprints",
                    earned: true,
                    earned_at: "2026-07-15T12:00:00-03:00",
                },
            ],
        },
    ],
    recent_badges: [],
};

function renderView(studentId = 1, semester = "2026.1") {
    return render(<StudentDashboardView studentId={studentId} semester={semester} />);
}

describe("StudentDashboardView", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(studentsApi.dashboard).mockResolvedValue(baseDashboard);
        vi.mocked(studentsApi.aiSummary).mockResolvedValue({ data: { summary: "Resumo gerado pela IA." } });
        vi.mocked(studentsApi.gamification).mockResolvedValue({ data: gamificationProfile });
    });

    it("renders student header, stats and the AI summary, fetching by the given props", async () => {
        renderView();

        await waitFor(() => {
            expect(screen.getByRole("heading", { level: 2, name: "Maria Estudante" })).toBeInTheDocument();
        });

        expect(screen.getByText(/Engenharia/)).toBeInTheDocument();
        await waitFor(() => {
            expect(screen.getByText("Resumo gerado pela IA.")).toBeInTheDocument();
        });

        expect(studentsApi.dashboard).toHaveBeenCalledWith(1, "2026.1");
        expect(studentsApi.aiSummary).toHaveBeenCalledWith(1, "2026.1");
    });

    it("renders the AI summary as sanitized markdown", async () => {
        vi.mocked(studentsApi.aiSummary).mockResolvedValue({ data: { summary: "**Ótimo** aluno" } });

        const { container } = renderView();

        await waitFor(() => {
            expect(screen.getByText("Ótimo")).toBeInTheDocument();
        });

        expect(screen.getByText("Ótimo").tagName).toBe("STRONG");
        expect(container.querySelector("strong")).not.toBeNull();
    });

    it("preserves presentations and certificates in their tabs", async () => {
        renderView();

        await waitFor(() => expect(screen.getByText("Seminário X")).toBeInTheDocument());
        expect(screen.getByRole("tab", { name: "Apresentações" })).toHaveAttribute("data-state", "active");

        const user = userEvent.setup();
        await user.click(screen.getByRole("tab", { name: "Certificados" }));

        expect(await screen.findByRole("link", { name: "Baixar" })).toHaveAttribute(
            "href",
            "https://example.test/certificado/abc-123",
        );
    });

    it("shows a muted message when the AI summary is not configured", async () => {
        vi.mocked(studentsApi.aiSummary).mockRejectedValue(
            new AdminApiError("ai_not_configured", "not configured", 503),
        );

        renderView();

        await waitFor(() => {
            expect(screen.getByText(/não configurad/i)).toBeInTheDocument();
        });
    });

    it("shows a generic error message when the AI summary fails for another reason", async () => {
        vi.mocked(studentsApi.aiSummary).mockRejectedValue(
            new AdminApiError("ai_request_failed", "upstream error", 502),
        );

        renderView();

        await waitFor(() => {
            expect(
                screen.getByText("Não foi possível gerar o resumo. Tente novamente mais tarde."),
            ).toBeInTheDocument();
        });
    });

    it("shows loading skeletons while the dashboard is fetching", () => {
        vi.mocked(studentsApi.dashboard).mockReturnValue(new Promise(() => {}));
        vi.mocked(studentsApi.aiSummary).mockReturnValue(new Promise(() => {}));

        renderView();

        const skeletons = document.querySelectorAll('[class*="animate-pulse"]');
        expect(skeletons.length).toBeGreaterThan(0);
    });

    it("shows 'Aluno não encontrado' when the student does not exist", async () => {
        vi.mocked(studentsApi.dashboard).mockRejectedValue(
            new AdminApiError("not_found", "student not found", 404),
        );

        renderView();

        await waitFor(() => {
            expect(screen.getByText("Aluno não encontrado.")).toBeInTheDocument();
        });
    });

    it("shows a generic error message when the dashboard fetch fails for another reason", async () => {
        vi.mocked(studentsApi.dashboard).mockRejectedValue(
            new AdminApiError("server_error", "boom", 500),
        );

        renderView();

        await waitFor(() => {
            expect(
                screen.getByText("Não foi possível carregar os dados do aluno. Tente novamente mais tarde."),
            ).toBeInTheDocument();
        });
    });

    it("fetches using the exact studentId and semester props (no URL parsing)", async () => {
        renderView(42, "2025.2");

        await waitFor(() => {
            expect(screen.getByText("Maria Estudante")).toBeInTheDocument();
        });

        expect(studentsApi.dashboard).toHaveBeenCalledWith(42, "2025.2");
        expect(studentsApi.aiSummary).toHaveBeenCalledWith(42, "2025.2");
    });

    it("shows a skeleton in the AI summary card while it is loading", async () => {
        vi.mocked(studentsApi.aiSummary).mockReturnValue(new Promise(() => {}));

        renderView();

        await waitFor(() => {
            expect(screen.getByText("Maria Estudante")).toBeInTheDocument();
        });

        expect(screen.queryByText("Resumo gerado pela IA.")).not.toBeInTheDocument();
        const skeletons = document.querySelectorAll('[class*="animate-pulse"]');
        expect(skeletons.length).toBeGreaterThan(0);
    });

    it("shows a placeholder when a presentation has no seminar type", async () => {
        vi.mocked(studentsApi.dashboard).mockResolvedValue({
            data: {
                ...baseDashboard.data,
                registrations: [
                    {
                        ...baseDashboard.data.registrations[0],
                        seminar: { ...baseDashboard.data.registrations[0].seminar, seminar_type: null },
                    },
                ],
            },
        });

        renderView();

        await waitFor(() => {
            expect(screen.getByText("—")).toBeInTheDocument();
        });
    });

    it("shows empty-state messages when there are no registrations or certificates", async () => {
        vi.mocked(studentsApi.dashboard).mockResolvedValue({
            data: {
                ...baseDashboard.data,
                registrations: [],
                certificates: [],
            },
        });

        renderView();

        await waitFor(() => {
            expect(screen.getByText("Nenhuma apresentação neste semestre.")).toBeInTheDocument();
        });

        const user = userEvent.setup();
        await user.click(screen.getByRole("tab", { name: "Certificados" }));

        await waitFor(() => {
            expect(screen.getByText("Nenhum certificado emitido neste semestre.")).toBeInTheDocument();
        });
    });

    it("renders the attendance and hours-by-type charts", async () => {
        renderView();

        await waitFor(() => expect(screen.getByText("Presença Geral")).toBeInTheDocument());
        expect(screen.getByText("Apresentações por Tipo")).toBeInTheDocument();
        expect(screen.getByText("Horas por Tipo")).toBeInTheDocument();
    });

    it("adds a lifetime Conquistas tab without adding the semester to its query key", async () => {
        const queryClient = new QueryClient({
            defaultOptions: { queries: { retry: false } },
        });
        const user = userEvent.setup();
        const { rerender } = render(
            <StudentDashboardView studentId={42} semester="2026.1" />,
            { queryClient },
        );

        await screen.findByRole("heading", { level: 2, name: "Maria Estudante" });
        await user.click(screen.getByRole("tab", { name: "Conquistas" }));

        expect(await screen.findByText("1 de 30 conquistas")).toBeInTheDocument();
        expect(studentsApi.gamification).toHaveBeenCalledWith(42);
        expect(queryClient.getQueryData(["admin-student-gamification", 42]))
            .toEqual({ data: gamificationProfile });
        expect(queryClient.getQueryData(["admin-student-gamification", 42, "2026.1"]))
            .toBeUndefined();

        rerender(<StudentDashboardView studentId={42} semester="2025.2" />);

        await waitFor(() => {
            expect(studentsApi.dashboard).toHaveBeenCalledWith(42, "2025.2");
            expect(studentsApi.aiSummary).toHaveBeenCalledWith(42, "2025.2");
        });
        await screen.findByRole("heading", { level: 2, name: "Maria Estudante" });
        expect(await screen.findByText("Resumo gerado pela IA.")).toBeInTheDocument();
        await user.click(screen.getByRole("tab", { name: "Conquistas" }));

        expect(studentsApi.gamification).toHaveBeenCalledTimes(1);
        expect(await screen.findByText("1 de 30 conquistas")).toBeInTheDocument();
    });
});
