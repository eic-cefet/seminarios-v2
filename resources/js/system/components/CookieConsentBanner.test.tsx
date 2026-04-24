import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, act } from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import { ConsentProvider } from "@shared/contexts/ConsentContext";
import { CookieConsentBanner } from "./CookieConsentBanner";

vi.mock("@shared/api/client", () => ({
    consentsApi: { record: vi.fn(() => Promise.resolve({ data: {} })) },
}));

function renderBanner() {
    return render(
        <MemoryRouter>
            <ConsentProvider>
                <CookieConsentBanner />
            </ConsentProvider>
        </MemoryRouter>,
    );
}

describe("CookieConsentBanner", () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it("renders accept, reject, and customize controls", () => {
        renderBanner();
        expect(screen.getByRole("button", { name: /aceitar todos/i })).toBeInTheDocument();
        expect(screen.getByRole("button", { name: /rejeitar todos/i })).toBeInTheDocument();
        expect(screen.getByRole("button", { name: /personalizar/i })).toBeInTheDocument();
    });

    it("exposes a link to the privacy policy", () => {
        renderBanner();
        expect(screen.getByRole("link", { name: /pol[ií]tica de privacidade/i }))
            .toHaveAttribute("href", "/politica-de-privacidade");
    });

    it("hides after the user accepts all", async () => {
        renderBanner();
        fireEvent.click(screen.getByRole("button", { name: /aceitar todos/i }));
        await Promise.resolve();
        expect(screen.queryByRole("button", { name: /aceitar todos/i })).toBeNull();
    });

    it("hides after the user rejects all", async () => {
        renderBanner();
        fireEvent.click(screen.getByRole("button", { name: /rejeitar todos/i }));
        await Promise.resolve();
        expect(screen.queryByRole("button", { name: /rejeitar todos/i })).toBeNull();
    });

    it("defaults optional categories to OFF in customize mode", () => {
        renderBanner();
        fireEvent.click(screen.getByRole("button", { name: /personalizar/i }));
        const analytics = screen.getByRole("checkbox", { name: /análise/i }) as HTMLInputElement;
        expect(analytics.checked).toBe(false);
    });

    it("shows all three cookie categories in customize mode", () => {
        renderBanner();
        fireEvent.click(screen.getByRole("button", { name: /personalizar/i }));
        expect(screen.getByRole("checkbox", { name: /essenciais/i })).toBeInTheDocument();
        expect(screen.getByRole("checkbox", { name: /funcionais/i })).toBeInTheDocument();
        expect(screen.getByRole("checkbox", { name: /análise/i })).toBeInTheDocument();
    });

    it("allows toggling optional cookies and saving preferences", async () => {
        renderBanner();
        fireEvent.click(screen.getByRole("button", { name: /personalizar/i }));

        const functional = screen.getByRole("checkbox", { name: /funcionais/i }) as HTMLInputElement;
        expect(functional.checked).toBe(false);
        fireEvent.click(functional);
        expect(functional.checked).toBe(true);

        const analytics = screen.getByRole("checkbox", { name: /análise/i }) as HTMLInputElement;
        fireEvent.click(analytics);
        expect(analytics.checked).toBe(true);

        await act(async () => {
            fireEvent.click(screen.getByRole("button", { name: /salvar prefer/i }));
        });
        // Banner should close after saving
        expect(screen.queryByRole("button", { name: /salvar prefer/i })).toBeNull();
    });

    it("navigates back to summary mode from customize mode", () => {
        renderBanner();
        fireEvent.click(screen.getByRole("button", { name: /personalizar/i }));
        expect(screen.getByRole("button", { name: /voltar/i })).toBeInTheDocument();
        fireEvent.click(screen.getByRole("button", { name: /voltar/i }));
        expect(screen.getByRole("button", { name: /aceitar todos/i })).toBeInTheDocument();
    });

    it("closes the banner from customize mode via the close button", () => {
        renderBanner();
        fireEvent.click(screen.getByRole("button", { name: /personalizar/i }));
        fireEvent.click(screen.getByRole("button", { name: /fechar/i }));
        expect(screen.queryByRole("dialog")).toBeNull();
    });
});
