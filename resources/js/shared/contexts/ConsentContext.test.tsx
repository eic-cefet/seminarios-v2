import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, act } from "@testing-library/react";
import { ConsentProvider, useConsent } from "./ConsentContext";

vi.mock("@shared/api/client", () => ({
    consentsApi: {
        record: vi.fn(() => Promise.resolve({ data: {} })),
    },
}));

function Probe() {
    const ctx = useConsent();
    return (
        <div>
            <span data-testid="decided">{String(ctx.hasDecided)}</span>
            <span data-testid="analytics">{String(ctx.state?.analytics ?? false)}</span>
            <button onClick={() => ctx.acceptAll()}>accept-all</button>
            <button onClick={() => ctx.rejectAll()}>reject-all</button>
        </div>
    );
}

describe("ConsentContext", () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it("starts undecided on first mount", () => {
        render(
            <ConsentProvider>
                <Probe />
            </ConsentProvider>,
        );
        expect(screen.getByTestId("decided").textContent).toBe("false");
    });

    it("records acceptance of all categories", async () => {
        render(
            <ConsentProvider>
                <Probe />
            </ConsentProvider>,
        );

        await act(async () => {
            screen.getByText("accept-all").click();
        });

        expect(screen.getByTestId("decided").textContent).toBe("true");
        expect(screen.getByTestId("analytics").textContent).toBe("true");
    });

    it("records rejection of optional categories", async () => {
        render(
            <ConsentProvider>
                <Probe />
            </ConsentProvider>,
        );

        await act(async () => {
            screen.getByText("reject-all").click();
        });

        expect(screen.getByTestId("decided").textContent).toBe("true");
        expect(screen.getByTestId("analytics").textContent).toBe("false");
    });
});
