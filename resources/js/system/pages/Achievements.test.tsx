import { QueryClient } from "@tanstack/react-query";
import { render, screen, userEvent, waitFor } from "@/test/test-utils";
import { createUser } from "@/test/factories";
import type { GamificationProfile } from "@shared/types";
import Achievements from "./Achievements";

vi.mock("@shared/contexts/AuthContext", () => ({
    useAuth: vi.fn(),
}));

vi.mock("@shared/api/client", () => ({
    profileApi: { gamification: vi.fn() },
}));

vi.mock("@shared/lib/analytics", () => ({
    analytics: { event: vi.fn(), pageview: vi.fn() },
}));

import { profileApi } from "@shared/api/client";
import { useAuth } from "@shared/contexts/AuthContext";

const badge = {
    key: "first_presence",
    name: "Primeiro Passo",
    description: "Participe de uma apresentação.",
    category: "Participação",
    tier: "bronze" as const,
    icon: "Footprints",
    earned: true,
    earned_at: "2026-07-15T12:00:00-03:00",
};

const profile: GamificationProfile = {
    progress: {
        total_xp: 650,
        level: 4,
        rank: "Curioso",
        current_level_xp: 600,
        next_level_xp: 1000,
        progress_percent: 12,
    },
    summary: { earned_badges: 12, total_badges: 30 },
    categories: [
        { key: "participacao", label: "Participação", badges: [badge] },
    ],
    recent_badges: Array.from({ length: 6 }, (_, index) => ({
        ...badge,
        key: `badge_${index}`,
        name: `Conquista recente ${index + 1}`,
    })),
};

function authenticated() {
    vi.mocked(useAuth).mockReturnValue({
        user: createUser(),
        isLoading: false,
        isAuthenticated: true,
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
        exchangeCode: vi.fn(),
        refreshUser: vi.fn(),
        completeTwoFactor: vi.fn(),
    });
}

describe("Achievements", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        authenticated();
    });

    it("does not expose the protected page while logged out", () => {
        vi.mocked(useAuth).mockReturnValue({
            user: null,
            isLoading: false,
            isAuthenticated: false,
            login: vi.fn(),
            register: vi.fn(),
            logout: vi.fn(),
            exchangeCode: vi.fn(),
            refreshUser: vi.fn(),
            completeTwoFactor: vi.fn(),
        });

        render(<Achievements />, {
            routerProps: { initialEntries: ["/conquistas"] },
        });

        expect(
            screen.queryByRole("heading", { name: "Minhas conquistas" }),
        ).not.toBeInTheDocument();
    });

    it("uses the profile gamification query key and renders summary plus five recent badges", async () => {
        vi.mocked(profileApi.gamification).mockResolvedValue({ data: profile });
        const queryClient = new QueryClient({
            defaultOptions: { queries: { retry: false } },
        });

        render(<Achievements />, { queryClient });

        expect(await screen.findByText("12 de 30 conquistas")).toBeInTheDocument();
        expect(profileApi.gamification).toHaveBeenCalledTimes(1);
        expect(queryClient.getQueryData(["profile", "gamification"])).toBeDefined();
        expect(screen.getByText("Conquista recente 5")).toBeInTheDocument();
        expect(screen.queryByText("Conquista recente 6")).not.toBeInTheDocument();
    });

    it("shows a loading skeleton while the profile is pending", () => {
        vi.mocked(profileApi.gamification).mockReturnValue(new Promise(() => {}));

        render(<Achievements />);

        expect(screen.getByRole("status", { name: "Carregando conquistas" }))
            .toBeInTheDocument();
    });

    it("shows a retryable error state", async () => {
        vi.mocked(profileApi.gamification)
            .mockRejectedValueOnce(new Error("offline"))
            .mockResolvedValueOnce({ data: profile });
        const user = userEvent.setup();

        render(<Achievements />);

        expect(
            await screen.findByText("Não foi possível carregar suas conquistas."),
        ).toBeInTheDocument();
        await user.click(screen.getByRole("button", { name: "Tentar novamente" }));

        await waitFor(() => {
            expect(profileApi.gamification).toHaveBeenCalledTimes(2);
        });
        expect(await screen.findByText("12 de 30 conquistas")).toBeInTheDocument();
    });

    it("shows an empty earned state without hiding locked gallery cards", async () => {
        vi.mocked(profileApi.gamification).mockResolvedValue({
            data: {
                ...profile,
                summary: { earned_badges: 0, total_badges: 30 },
                categories: [
                    {
                        ...profile.categories[0],
                        badges: [{ ...badge, earned: false, earned_at: null }],
                    },
                ],
                recent_badges: [],
            },
        });

        render(<Achievements />);

        expect(
            await screen.findByText("Você ainda não conquistou nenhuma conquista."),
        ).toBeInTheDocument();
        expect(screen.getByText("Primeiro Passo")).toBeInTheDocument();
        expect(screen.getByText("Bloqueada")).toBeInTheDocument();
    });
});
