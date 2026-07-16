import { QueryClient } from "@tanstack/react-query";
import type { BadgeTier, GamificationProfile } from "@shared/types";
import { render, screen, userEvent, waitFor, within } from "@/test/test-utils";
import { AdminApiError, studentsApi } from "../../api/adminClient";
import { StudentGamificationPanel } from "./StudentGamificationPanel";

vi.mock("../../api/adminClient", async (importOriginal) => {
    const actual = await importOriginal<typeof import("../../api/adminClient")>();
    return {
        ...actual,
        studentsApi: { ...actual.studentsApi, gamification: vi.fn() },
    };
});

const categoryLabels = [
    "Participação",
    "Exploração",
    "Comunidade",
    "Consistência",
    "Workshops",
    "Avaliações",
];
const tiers: BadgeTier[] = ["bronze", "silver", "gold", "platinum", "special"];

function makeProfile(earnedBadges = 2): GamificationProfile {
    let badgeIndex = 0;
    const categories = categoryLabels.map((label, categoryIndex) => ({
        key: `categoria_${categoryIndex + 1}`,
        label,
        badges: Array.from({ length: 5 }, () => {
            badgeIndex += 1;
            const earned = badgeIndex <= earnedBadges;

            return {
                key: `badge_${badgeIndex}`,
                name: `Conquista ${badgeIndex}`,
                description: `Complete o requisito ${badgeIndex}.`,
                category: label,
                tier: tiers[(badgeIndex - 1) % tiers.length],
                icon: "Award",
                earned,
                earned_at: earned ? "2026-07-15T12:00:00-03:00" : null,
            };
        }),
    }));

    return {
        progress: {
            total_xp: 650,
            level: 4,
            rank: "Curioso",
            current_level_xp: 600,
            next_level_xp: 1000,
            progress_percent: 12,
        },
        summary: { earned_badges: earnedBadges, total_badges: 30 },
        categories,
        recent_badges: categories.flatMap((category) => category.badges).filter((badge) => badge.earned),
    };
}

describe("StudentGamificationPanel", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("uses the lifetime-only query key and shows level, progress, summary, and every badge in six groups", async () => {
        const profile = makeProfile();
        vi.mocked(studentsApi.gamification).mockResolvedValue({ data: profile });
        const queryClient = new QueryClient({
            defaultOptions: { queries: { retry: false } },
        });

        render(<StudentGamificationPanel userId={42} />, { queryClient });

        expect(await screen.findByRole("heading", { name: "Nível 4" })).toBeInTheDocument();
        expect(screen.getByText("Curioso")).toBeInTheDocument();
        expect(screen.getByText("650 XP no total")).toBeInTheDocument();
        expect(screen.getByText("Próximo nível: 1.000 XP")).toBeInTheDocument();
        expect(screen.getByRole("progressbar", { name: "Progresso para o nível 5" }))
            .toHaveAttribute("aria-valuenow", "12");
        expect(screen.getByText("2 de 30 conquistas")).toBeInTheDocument();

        for (const label of categoryLabels) {
            expect(screen.getByRole("heading", { name: label })).toBeInTheDocument();
        }
        expect(screen.getAllByRole("article")).toHaveLength(30);
        const earnedCard = screen.getByRole("article", { name: "Conquista 1, Conquistada" });
        expect(earnedCard).toHaveTextContent("Bronze");
        expect(earnedCard)
            .toHaveTextContent("Conquistada em 15/07/2026");
        const earnedStatus = within(earnedCard).getByText("Conquistada");
        expect(earnedStatus).toHaveClass(
            "bg-green-800",
            "text-white",
            "dark:bg-green-300",
            "dark:text-green-950",
        );
        expect(earnedStatus).not.toHaveClass("bg-green-600");
        expect(screen.getByRole("article", { name: "Conquista 3, Bloqueada" }))
            .toHaveTextContent("Requisito: Complete o requisito 3.");
        expect(screen.getAllByText("Prata").length).toBeGreaterThan(0);
        expect(screen.getAllByText("Ouro").length).toBeGreaterThan(0);
        expect(screen.getAllByText("Platina").length).toBeGreaterThan(0);
        expect(screen.getAllByText("Especial").length).toBeGreaterThan(0);
        expect(studentsApi.gamification).toHaveBeenCalledWith(42);
        expect(queryClient.getQueryData(["admin-student-gamification", 42])).toEqual({ data: profile });
        expect(queryClient.getQueryData(["admin-student-gamification", 42, "2026.1"]))
            .toBeUndefined();
        expect(screen.queryByText(/evento de xp|fonte de xp|attendance:/i)).not.toBeInTheDocument();
    });

    it("shows an accessible loading state", () => {
        vi.mocked(studentsApi.gamification).mockReturnValue(new Promise(() => {}));

        render(<StudentGamificationPanel userId={42} />);

        expect(screen.getByRole("status", { name: "Carregando conquistas do aluno" }))
            .toBeInTheDocument();
    });

    it("shows the authorization error without leaking profile data", async () => {
        vi.mocked(studentsApi.gamification).mockRejectedValue(
            new AdminApiError("forbidden", "Forbidden", 403),
        );

        render(<StudentGamificationPanel userId={42} />);

        expect(await screen.findByRole("alert")).toHaveTextContent(
            "Você não tem permissão para visualizar as conquistas deste aluno.",
        );
        expect(screen.queryByText("650 XP no total")).not.toBeInTheDocument();
    });

    it("retries a failed request", async () => {
        vi.mocked(studentsApi.gamification)
            .mockRejectedValueOnce(new AdminApiError("server_error", "Error", 500))
            .mockResolvedValueOnce({ data: makeProfile() });
        const user = userEvent.setup();

        render(<StudentGamificationPanel userId={42} />);

        expect(await screen.findByText("Não foi possível carregar as conquistas do aluno."))
            .toBeInTheDocument();
        await user.click(screen.getByRole("button", { name: "Tentar novamente" }));

        await waitFor(() => {
            expect(studentsApi.gamification).toHaveBeenCalledTimes(2);
        });
        expect(await screen.findByText("2 de 30 conquistas")).toBeInTheDocument();
    });

    it("shows empty progress while keeping all locked badges visible", async () => {
        vi.mocked(studentsApi.gamification).mockResolvedValue({ data: makeProfile(0) });

        render(<StudentGamificationPanel userId={42} />);

        expect(await screen.findByText("0 de 30 conquistas")).toBeInTheDocument();
        expect(screen.getByText("Este aluno ainda não conquistou nenhuma conquista."))
            .toBeInTheDocument();
        expect(screen.getAllByRole("article")).toHaveLength(30);
        expect(screen.getAllByText("Bloqueada")).toHaveLength(30);
    });
});
