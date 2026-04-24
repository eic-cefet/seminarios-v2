import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
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

    it("defaults optional categories to OFF in customize mode", () => {
        renderBanner();
        fireEvent.click(screen.getByRole("button", { name: /personalizar/i }));
        const analytics = screen.getByLabelText(/análise/i) as HTMLInputElement;
        expect(analytics.checked).toBe(false);
    });
});
