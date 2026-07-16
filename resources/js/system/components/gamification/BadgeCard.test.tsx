import { render, screen } from "@/test/test-utils";
import type { GamificationBadge } from "@shared/types";
import { BadgeCard } from "./BadgeCard";

const earnedBadge: GamificationBadge = {
    key: "first_presence",
    name: "Primeiro Passo",
    description: "Participe de uma apresentação.",
    category: "Participação",
    tier: "bronze",
    icon: "Footprints",
    earned: true,
    earned_at: "2026-07-15T12:00:00-03:00",
};

describe("BadgeCard", () => {
    it("shows earned status, tier text, and a PT-BR earned date", () => {
        render(<BadgeCard badge={earnedBadge} />);

        expect(screen.getByRole("heading", { name: "Primeiro Passo" })).toBeInTheDocument();
        expect(screen.getByText("Bronze")).toBeInTheDocument();
        expect(screen.getByText("Conquistada")).toBeInTheDocument();
        expect(screen.getByText("Conquistada em 15/07/2026")).toBeInTheDocument();
    });

    it("shows locked status and the visible requirement", () => {
        render(
            <BadgeCard
                badge={{
                    ...earnedBadge,
                    key: "attendance_5",
                    name: "Pegando Ritmo",
                    earned: false,
                    earned_at: null,
                }}
            />,
        );

        expect(screen.getByText("Bloqueada")).toBeInTheDocument();
        expect(
            screen.getByText("Requisito: Participe de uma apresentação."),
        ).toBeInTheDocument();
    });

    it("falls back to Award for an unknown backend icon", () => {
        const { container } = render(
            <BadgeCard badge={{ ...earnedBadge, icon: "UnknownIcon" }} />,
        );

        expect(container.querySelector("svg.lucide-award")).toBeInTheDocument();
    });
});
