import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, act } from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import { HelmetProvider } from "react-helmet-async";
import { ConsentProvider } from "@shared/contexts/ConsentContext";
import CookiePreferences from "./CookiePreferences";

vi.mock("@shared/api/client", () => ({
    consentsApi: {
        record: vi.fn(() => Promise.resolve({ data: {} })),
        list: vi.fn(() => Promise.resolve({ data: [] })),
    },
}));

vi.mock("@shared/contexts/AuthContext", () => ({
    useAuth: vi.fn(() => ({
        user: null,
        isLoading: false,
        isAuthenticated: false,
        login: vi.fn(),
        register: vi.fn(),
        logout: vi.fn(),
        exchangeCode: vi.fn(),
        refreshUser: vi.fn(),
        completeTwoFactor: vi.fn(),
    })),
    AuthProvider: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

function renderPage() {
    return render(
        <HelmetProvider>
            <MemoryRouter>
                <ConsentProvider>
                    <CookiePreferences />
                </ConsentProvider>
            </MemoryRouter>
        </HelmetProvider>,
    );
}

describe("CookiePreferences", () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it("lets the user toggle the analytics category and save", async () => {
        renderPage();

        const analytics = await screen.findByRole("checkbox", { name: /análise/i }) as HTMLInputElement;
        expect(analytics.checked).toBe(false);
        fireEvent.click(analytics);
        expect(analytics.checked).toBe(true);

        fireEvent.click(screen.getByRole("button", { name: /salvar/i }));
        // no throw = pass
    });

    it("lets the user toggle the functional category", async () => {
        renderPage();

        const functional = await screen.findByRole("checkbox", { name: /funcionais/i }) as HTMLInputElement;
        expect(functional.checked).toBe(false);
        fireEvent.click(functional);
        expect(functional.checked).toBe(true);
    });

    it("shows saved timestamp after successful save", async () => {
        renderPage();

        await act(async () => {
            fireEvent.click(screen.getByRole("button", { name: /salvar/i }));
        });

        expect(screen.getByText(/salvo em/i)).toBeInTheDocument();
    });
});
