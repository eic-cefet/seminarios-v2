import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, act } from "@testing-library/react";
import { ConsentProvider, useConsent } from "./ConsentContext";
import * as client from "@shared/api/client";

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
            <span data-testid="is-open">{String(ctx.isOpen)}</span>
            <button onClick={() => ctx.acceptAll()}>accept-all</button>
            <button onClick={() => ctx.rejectAll()}>reject-all</button>
            <button onClick={() => ctx.reopen()}>reopen</button>
            <button onClick={() => ctx.close()}>close</button>
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

    it("silently swallows API errors during sync", async () => {
        vi.spyOn(client.consentsApi, "record").mockRejectedValueOnce(
            new Error("network error"),
        );

        render(
            <ConsentProvider>
                <Probe />
            </ConsentProvider>,
        );

        await act(async () => {
            screen.getByText("accept-all").click();
        });

        // State should still be updated even when API fails
        expect(screen.getByTestId("decided").textContent).toBe("true");
    });

    it("reopen restores the banner after it was accepted", async () => {
        render(
            <ConsentProvider>
                <Probe />
            </ConsentProvider>,
        );

        await act(async () => {
            screen.getByText("accept-all").click();
        });
        expect(screen.getByTestId("is-open").textContent).toBe("false");

        await act(async () => {
            screen.getByText("reopen").click();
        });
        expect(screen.getByTestId("is-open").textContent).toBe("true");
    });

    it("close dismisses the banner", async () => {
        render(
            <ConsentProvider>
                <Probe />
            </ConsentProvider>,
        );

        // First open it via reopen
        await act(async () => {
            screen.getByText("reopen").click();
        });
        expect(screen.getByTestId("is-open").textContent).toBe("true");

        await act(async () => {
            screen.getByText("close").click();
        });
        expect(screen.getByTestId("is-open").textContent).toBe("false");
    });

    it("throws when useConsent is called outside ConsentProvider", () => {
        const consoleError = vi.spyOn(console, "error").mockImplementation(() => undefined);

        function BadConsumer() {
            useConsent();
            return null;
        }

        expect(() => render(<BadConsumer />)).toThrow(
            "useConsent must be used inside ConsentProvider",
        );

        consoleError.mockRestore();
    });
});
