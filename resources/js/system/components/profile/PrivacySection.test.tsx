import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { MemoryRouter } from "react-router-dom";
import { PrivacySection } from "./PrivacySection";

const mockRequestExport = vi.fn(() => Promise.resolve({ data: { id: 1 } }));
const mockListExports = vi.fn(() => Promise.resolve({ data: [] }));
const mockRequestDeletion = vi.fn((_password: string) =>
    Promise.resolve({ message: "ok", scheduled_for: "2026-05-23T00:00:00Z" }),
);
const mockCancelDeletion = vi.fn(() => Promise.resolve({ message: "ok" }));

vi.mock("@shared/api/client", () => ({
    dataPrivacyApi: {
        listExports: () => mockListExports(),
        requestExport: () => mockRequestExport(),
        requestDeletion: (p: string) => mockRequestDeletion(p),
        cancelDeletion: () => mockCancelDeletion(),
    },
}));

const baseUser = {
    id: 1,
    name: "Ana",
    email: "ana@example.com",
    anonymization_requested_at: null as string | null,
};

function renderSection(overrides: Partial<typeof baseUser> = {}) {
    const client = new QueryClient({
        defaultOptions: { queries: { retry: false } },
    });
    return render(
        <QueryClientProvider client={client}>
            <MemoryRouter>
                <PrivacySection
                    user={{ ...baseUser, ...overrides }}
                    onUpdate={vi.fn()}
                />
            </MemoryRouter>
        </QueryClientProvider>,
    );
}

describe("PrivacySection", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("requests a data export", async () => {
        renderSection();
        fireEvent.click(
            screen.getByRole("button", { name: /exportar meus dados/i }),
        );
        await waitFor(() => expect(mockRequestExport).toHaveBeenCalled());
    });

    it("requires confirmation + password to delete account", async () => {
        renderSection();
        fireEvent.click(
            screen.getByRole("button", { name: /excluir minha conta/i }),
        );
        const input = screen.getByLabelText(/senha/i);
        fireEvent.change(input, { target: { value: "password" } });
        fireEvent.click(
            screen.getByRole("button", { name: /^confirmar exclusão$/i }),
        );
        await waitFor(() =>
            expect(mockRequestDeletion).toHaveBeenCalledWith("password"),
        );
    });

    it("shows cancel CTA when deletion is pending", async () => {
        renderSection({ anonymization_requested_at: "2026-04-20T00:00:00Z" });
        fireEvent.click(
            screen.getByRole("button", { name: /cancelar exclusão/i }),
        );
        await waitFor(() => expect(mockCancelDeletion).toHaveBeenCalled());
    });
});
