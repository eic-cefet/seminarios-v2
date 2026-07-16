import { render, screen } from "@/test/test-utils";
import { LevelProgress } from "./LevelProgress";

describe("LevelProgress", () => {
    it("presents level, XP thresholds, and accessible progress semantics", () => {
        render(
            <LevelProgress
                progress={{
                    total_xp: 650,
                    level: 4,
                    rank: "Curioso",
                    current_level_xp: 600,
                    next_level_xp: 1000,
                    progress_percent: 12,
                }}
            />,
        );

        expect(screen.getByRole("heading", { name: "Nível 4" })).toBeInTheDocument();
        expect(screen.getByText("Curioso")).toBeInTheDocument();
        expect(screen.getByText("650 XP no total")).toBeInTheDocument();
        expect(screen.getByText("Próximo nível: 1.000 XP")).toBeInTheDocument();

        const progressbar = screen.getByRole("progressbar", {
            name: "Progresso para o nível 5",
        });
        expect(progressbar).toHaveAttribute("aria-valuemin", "0");
        expect(progressbar).toHaveAttribute("aria-valuemax", "100");
        expect(progressbar).toHaveAttribute("aria-valuenow", "12");
        expect(progressbar).toHaveAttribute("aria-valuetext", "12% concluído");
    });
});
