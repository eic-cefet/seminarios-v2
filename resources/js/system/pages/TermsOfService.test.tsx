import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@/test/test-utils";
import TermsOfService from "./TermsOfService";

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

describe("TermsOfService", () => {
    it("renders the main sections", () => {
        render(<TermsOfService />);
        ["aceitação", "cadastro", "conduta", "propriedade", "limitação", "lei aplicável"]
            .forEach((k) => expect(screen.getAllByText(new RegExp(k, "i")).length).toBeGreaterThan(0));
    });
});
