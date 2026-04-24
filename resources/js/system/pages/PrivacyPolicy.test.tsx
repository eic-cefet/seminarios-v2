import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@/test/test-utils";
import PrivacyPolicy from "./PrivacyPolicy";

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

describe("PrivacyPolicy", () => {
    it("renders the nine LGPD subject rights (Art. 18)", () => {
        render(<PrivacyPolicy />);
        ["confirmação", "acesso", "correção", "anonimização", "portabilidade", "eliminação", "compartilhamento", "consentimento", "revogar"]
            .forEach((keyword) => {
                expect(screen.getAllByText(new RegExp(keyword, "i")).length).toBeGreaterThan(0);
            });
    });

    it("lists third-party processors (AWS, OpenAI, Google, GitHub, email)", () => {
        render(<PrivacyPolicy />);
        ["AWS", "OpenAI", "Google", "GitHub"].forEach((name) => {
            expect(screen.getAllByText(new RegExp(name, "i")).length).toBeGreaterThan(0);
        });
    });

    it("exposes the DPO contact email", () => {
        render(<PrivacyPolicy />);
        expect(screen.getAllByText(/encarregado/i).length).toBeGreaterThan(0);
        expect(screen.getByRole("link", { name: /lgpd@eic-seminarios\.com/i })).toHaveAttribute(
            "href",
            "mailto:lgpd@eic-seminarios.com",
        );
    });
});
