import { render, screen, userEvent, waitFor } from "@/test/test-utils";
import type {
    GamificationBadge,
    GamificationSyncDelta,
} from "@shared/types";
import { axe } from "jest-axe";
import { useRef, useState } from "react";
import { AchievementUnlockDialog } from "./AchievementUnlockDialog";

const firstBadge: GamificationBadge = {
    key: "first_presence",
    name: "Primeiro Passo",
    description: "Participe de uma apresentacao.",
    category: "participation",
    tier: "bronze",
    icon: "Footprints",
    earned: true,
    earned_at: "2026-07-15T12:00:00-03:00",
};

const secondBadge: GamificationBadge = {
    ...firstBadge,
    key: "first_evaluation",
    name: "Voz Ativa",
    description: "Avalie uma apresentacao.",
    icon: "MessageSquare",
};

const delta = (
    overrides: Partial<GamificationSyncDelta> = {},
): GamificationSyncDelta => ({
    xp_earned: 45,
    total_xp: 145,
    level: {
        level: 2,
        rank: "Participante",
        current_level_xp: 45,
        next_level_xp: 100,
        progress_percent: 45,
    },
    new_badges: [firstBadge],
    ...overrides,
});

function ControlledDialog({ value }: { value: GamificationSyncDelta | null }) {
    const [open, setOpen] = useState(false);
    const returnFocusRef = useRef<HTMLHeadingElement>(null);

    return (
        <>
            <h1 ref={returnFocusRef} tabIndex={-1}>
                Resultado da ação
            </h1>
            <button type="button" onClick={() => setOpen(true)}>
                Mostrar resultado
            </button>
            <AchievementUnlockDialog
                delta={value}
                open={open}
                onOpenChange={setOpen}
                returnFocusRef={returnFocusRef}
            />
        </>
    );
}

describe("AchievementUnlockDialog", () => {
    it("shows the singular heading, XP, level, rank, earned badge, and gallery link", () => {
        render(
            <AchievementUnlockDialog
                delta={delta()}
                open
                onOpenChange={vi.fn()}
            />,
        );

        expect(
            screen.getByRole("heading", { name: "Conquista desbloqueada!" }),
        ).toBeInTheDocument();
        expect(screen.getByText("+45 XP")).toBeInTheDocument();
        expect(screen.getByText("Nível 2 · Participante")).toBeInTheDocument();
        expect(
            screen.getByRole("article", {
                name: "Primeiro Passo, Conquistada",
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole("link", { name: "Ver todas as conquistas" }),
        ).toHaveAttribute("href", "/conquistas");
    });

    it("shows the plural heading and every newly earned badge", () => {
        render(
            <AchievementUnlockDialog
                delta={delta({ new_badges: [firstBadge, secondBadge] })}
                open
                onOpenChange={vi.fn()}
            />,
        );

        expect(
            screen.getByRole("heading", { name: "Novas conquistas!" }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole("article", {
                name: "Primeiro Passo, Conquistada",
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole("article", { name: "Voz Ativa, Conquistada" }),
        ).toBeInTheDocument();
    });

    it("shows an XP-only heading and progress without badge cards", () => {
        render(
            <AchievementUnlockDialog
                delta={delta({ xp_earned: 20, new_badges: [] })}
                open
                onOpenChange={vi.fn()}
            />,
        );

        expect(
            screen.getByRole("heading", { name: "Você ganhou XP!" }),
        ).toBeInTheDocument();
        expect(screen.getByText("+20 XP")).toBeInTheDocument();
        expect(screen.getByText("Nível 2 · Participante")).toBeInTheDocument();
        expect(screen.queryByRole("article")).not.toBeInTheDocument();
    });

    it.each([
        ["null delta", null],
        ["zero delta", delta({ xp_earned: 0, new_badges: [] })],
    ])("does not open for a %s", (_label, value) => {
        render(
            <AchievementUnlockDialog
                delta={value}
                open
                onOpenChange={vi.fn()}
            />,
        );

        expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
    });

    it("closes with Continue and focuses the explicit return target", async () => {
        const user = userEvent.setup();
        render(<ControlledDialog value={delta()} />);

        const opener = screen.getByRole("button", { name: "Mostrar resultado" });
        opener.focus();
        await user.click(opener);
        await user.click(screen.getByRole("button", { name: "Continuar" }));

        await waitFor(() =>
            expect(
                screen.getByRole("heading", { name: "Resultado da ação" }),
            ).toHaveFocus(),
        );
        expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
    });

    it("closes with the labelled close button and Escape", async () => {
        const user = userEvent.setup();
        render(<ControlledDialog value={delta()} />);

        const opener = screen.getByRole("button", { name: "Mostrar resultado" });
        await user.click(opener);
        await user.click(screen.getByRole("button", { name: "Fechar" }));
        expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
        expect(
            screen.getByRole("heading", { name: "Resultado da ação" }),
        ).toHaveFocus();

        await user.click(opener);
        await user.keyboard("{Escape}");

        await waitFor(() =>
            expect(
                screen.getByRole("heading", { name: "Resultado da ação" }),
            ).toHaveFocus(),
        );
        expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
    });

    it("has no detectable accessibility violations", async () => {
        render(
            <AchievementUnlockDialog
                delta={delta()}
                open
                onOpenChange={vi.fn()}
            />,
        );

        expect(await axe(screen.getByRole("dialog"))).toHaveNoViolations();
    });
});
