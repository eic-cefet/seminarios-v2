import { render, screen, userEvent } from "@/test/test-utils";
import type { GamificationProfile } from "@shared/types";
import { BadgeGallery } from "./BadgeGallery";

const categories: GamificationProfile["categories"] = [
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
    {
        key: "workshops",
        label: "Workshops",
        badges: [
            {
                key: "first_workshop",
                name: "Workshop Concluído",
                description: "Conclua um workshop.",
                category: "Workshops",
                tier: "silver",
                icon: "Wrench",
                earned: false,
                earned_at: null,
            },
        ],
    },
];

describe("BadgeGallery", () => {
    it("filters cards using Todas and backend category filters", async () => {
        const user = userEvent.setup();
        render(<BadgeGallery categories={categories} />);

        expect(screen.getByRole("button", { name: "Todas" })).toHaveAttribute(
            "aria-pressed",
            "true",
        );
        expect(screen.getByText("Primeiro Passo")).toBeInTheDocument();
        expect(screen.getByText("Workshop Concluído")).toBeInTheDocument();

        await user.click(screen.getByRole("button", { name: "Workshops" }));

        expect(screen.queryByText("Primeiro Passo")).not.toBeInTheDocument();
        expect(screen.getByText("Workshop Concluído")).toBeInTheDocument();
        expect(screen.getByRole("button", { name: "Workshops" })).toHaveAttribute(
            "aria-pressed",
            "true",
        );

        await user.click(screen.getByRole("button", { name: "Todas" }));
        expect(screen.getByText("Primeiro Passo")).toBeInTheDocument();
    });

    it("uses the required responsive one, two, and three-column grid", () => {
        const { container } = render(<BadgeGallery categories={categories} />);

        expect(container.querySelector(".grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-3"))
            .toBeInTheDocument();
    });
});
