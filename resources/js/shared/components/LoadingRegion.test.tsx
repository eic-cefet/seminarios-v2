import { render, screen } from "@testing-library/react";
import { describe, it, expect } from "vitest";
import { LoadingRegion } from "./LoadingRegion";

describe("LoadingRegion", () => {
    it("exposes a polite status region with accessible label", () => {
        render(<LoadingRegion label="Carregando apresentações"><div /></LoadingRegion>);
        const region = screen.getByRole("status");
        expect(region).toHaveAttribute("aria-live", "polite");
        expect(region).toHaveAttribute("aria-busy", "true");
        expect(region).toHaveAccessibleName("Carregando apresentações");
    });

    it("renders children (skeletons) as visual content", () => {
        render(
            <LoadingRegion label="Carregando">
                <span data-testid="skeleton" />
            </LoadingRegion>,
        );
        expect(screen.getByTestId("skeleton")).toBeInTheDocument();
    });
});
