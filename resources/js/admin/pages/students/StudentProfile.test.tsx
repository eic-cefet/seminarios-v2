import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, waitFor, userEvent } from "@/test/test-utils";
import { Route, Routes } from "react-router-dom";
import StudentProfile from "./StudentProfile";
import { studentsApi, AdminApiError } from "../../api/adminClient";

vi.mock("../../api/adminClient", async (importOriginal) => {
    const actual = await importOriginal<typeof import("../../api/adminClient")>();
    return {
        ...actual,
        studentsApi: { dashboard: vi.fn(), aiSummary: vi.fn() },
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

function renderPage(initialEntry = "/students/1?semester=2026.1") {
    return render(
        <Routes>
            <Route path="/students/:userId" element={<StudentProfile />} />
        </Routes>,
        { routerProps: { initialEntries: [initialEntry] } },
    );
}

describe("StudentProfile", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(studentsApi.dashboard).mockResolvedValue(baseDashboard);
        vi.mocked(studentsApi.aiSummary).mockResolvedValue({ data: { summary: "Resumo gerado pela IA." } });
    });

    it("renders student header, stats and the AI summary, fetching by the numeric id from the URL", async () => {
        renderPage();

        await waitFor(() => {
            expect(screen.getByText("Maria Estudante")).toBeInTheDocument();
        });

        expect(screen.getByText(/Engenharia/)).toBeInTheDocument();
        await waitFor(() => {
            expect(screen.getByText("Resumo gerado pela IA.")).toBeInTheDocument();
        });

        expect(studentsApi.dashboard).toHaveBeenCalledWith(1, "2026.1");
        expect(studentsApi.aiSummary).toHaveBeenCalledWith(1, "2026.1");
    });

    it("lists presentations and certificates in their tabs", async () => {
        renderPage();

        await waitFor(() => expect(screen.getByText("Seminário X")).toBeInTheDocument());
        expect(screen.getAllByText(/Seminário X/).length).toBeGreaterThan(0);
    });

    it("shows a muted message when the AI summary is not configured", async () => {
        vi.mocked(studentsApi.aiSummary).mockRejectedValue(
            new AdminApiError("ai_not_configured", "not configured", 503),
        );

        renderPage();

        await waitFor(() => {
            expect(screen.getByText(/não configurad/i)).toBeInTheDocument();
        });
    });

    it("shows a generic error message when the AI summary fails for another reason", async () => {
        vi.mocked(studentsApi.aiSummary).mockRejectedValue(
            new AdminApiError("ai_request_failed", "upstream error", 502),
        );

        renderPage();

        await waitFor(() => {
            expect(
                screen.getByText("Não foi possível gerar o resumo. Tente novamente mais tarde."),
            ).toBeInTheDocument();
        });
    });

    it("shows loading skeletons while the dashboard is fetching", () => {
        vi.mocked(studentsApi.dashboard).mockReturnValue(new Promise(() => {}));
        vi.mocked(studentsApi.aiSummary).mockReturnValue(new Promise(() => {}));

        renderPage();

        const skeletons = document.querySelectorAll('[class*="animate-pulse"]');
        expect(skeletons.length).toBeGreaterThan(0);
    });

    it("shows 'Aluno não encontrado' when the student does not exist", async () => {
        vi.mocked(studentsApi.dashboard).mockRejectedValue(
            new AdminApiError("not_found", "student not found", 404),
        );

        renderPage();

        await waitFor(() => {
            expect(screen.getByText("Aluno não encontrado.")).toBeInTheDocument();
        });
    });

    it("shows a generic error message when the dashboard fetch fails for another reason", async () => {
        vi.mocked(studentsApi.dashboard).mockRejectedValue(
            new AdminApiError("server_error", "boom", 500),
        );

        renderPage();

        await waitFor(() => {
            expect(
                screen.getByText("Não foi possível carregar os dados do aluno. Tente novamente mais tarde."),
            ).toBeInTheDocument();
        });
    });

    it("falls back to the current semester when none is provided in the URL", async () => {
        renderPage("/students/1");

        await waitFor(() => {
            expect(screen.getByText("Maria Estudante")).toBeInTheDocument();
        });

        expect(studentsApi.dashboard).toHaveBeenCalledWith(1, expect.stringMatching(/^\d{4}\.[12]$/));
    });

    it("shows a skeleton in the AI summary card while it is loading", async () => {
        vi.mocked(studentsApi.aiSummary).mockReturnValue(new Promise(() => {}));

        renderPage();

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

        renderPage();

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

        renderPage();

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
        renderPage();

        await waitFor(() => expect(screen.getByText("Presença Geral")).toBeInTheDocument());
        expect(screen.getByText("Apresentações por Tipo")).toBeInTheDocument();
        expect(screen.getByText("Horas por Tipo")).toBeInTheDocument();
    });
});
