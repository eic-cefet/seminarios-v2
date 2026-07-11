import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, waitFor } from "@/test/test-utils";
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

describe("StudentProfile", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.mocked(studentsApi.dashboard).mockResolvedValue(baseDashboard);
        vi.mocked(studentsApi.aiSummary).mockResolvedValue({ data: { summary: "Resumo gerado pela IA." } });
    });

    it("renders student header, stats and the AI summary", async () => {
        render(<StudentProfile />, {
            routerProps: { initialEntries: ["/students/1?semester=2026.1"] },
        });

        await waitFor(() => {
            expect(screen.getByText("Maria Estudante")).toBeInTheDocument();
        });

        await waitFor(() => {
            expect(screen.getByText(/Engenharia/)).toBeInTheDocument();
        });

        await waitFor(() => {
            expect(screen.getByText("Resumo gerado pela IA.")).toBeInTheDocument();
        });
    });

    it("lists presentations and certificates in their tabs", async () => {
        render(<StudentProfile />, {
            routerProps: { initialEntries: ["/students/1?semester=2026.1"] },
        });

        await waitFor(() => expect(screen.getByText("Seminário X")).toBeInTheDocument());
        expect(screen.getAllByText(/Seminário X/).length).toBeGreaterThan(0);
    });

    it("shows a muted message when the AI summary is not configured", async () => {
        vi.mocked(studentsApi.aiSummary).mockRejectedValue(
            new AdminApiError("ai_not_configured", "not configured", 503),
        );

        render(<StudentProfile />, {
            routerProps: { initialEntries: ["/students/1?semester=2026.1"] },
        });

        await waitFor(() => {
            expect(screen.getByText(/não configurad/i)).toBeInTheDocument();
        });
    });
});
