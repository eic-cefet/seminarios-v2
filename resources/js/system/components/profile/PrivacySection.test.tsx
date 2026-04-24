import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { MemoryRouter } from "react-router-dom";
import { PrivacySection } from "./PrivacySection";

const mockRequestExport = vi.fn(() => Promise.resolve({ data: { id: 1 } }));
type ExportRecord = { id: number; status: string; created_at?: string | null };
const mockListExports = vi.fn(() =>
    Promise.resolve({ data: [] as ExportRecord[] }),
);
const mockRequestDeletion = vi.fn((_password: string) =>
    Promise.resolve({ message: "Enviamos um link de confirmação para o seu e-mail." }),
);
const mockCancelDeletion = vi.fn(() => Promise.resolve({ message: "ok" }));

vi.mock("@shared/api/client", () => ({
    // Provide a no-op ApiRequestError class so errors.ts instanceof check works
    ApiRequestError: class extends Error {
        constructor(
            public code: string,
            message: string,
            public status: number,
        ) {
            super(message);
            this.name = "ApiRequestError";
        }
    },
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

    it("shows error when deletion request fails", async () => {
        mockRequestDeletion.mockRejectedValueOnce(new Error("Senha incorreta"));
        renderSection();
        fireEvent.click(
            screen.getByRole("button", { name: /excluir minha conta/i }),
        );
        const input = screen.getByLabelText(/senha/i);
        fireEvent.change(input, { target: { value: "wrong" } });
        fireEvent.click(
            screen.getByRole("button", { name: /^confirmar exclusão$/i }),
        );
        await waitFor(() =>
            expect(screen.getByText(/senha incorreta/i)).toBeInTheDocument(),
        );
    });

    it("shows error when export request fails", async () => {
        mockRequestExport.mockRejectedValueOnce(new Error("Muitas tentativas"));
        renderSection();
        fireEvent.click(
            screen.getByRole("button", { name: /exportar meus dados/i }),
        );
        await waitFor(() =>
            expect(
                screen.getByText(/muitas tentativas/i),
            ).toBeInTheDocument(),
        );
    });

    it("shows recent export requests when available", async () => {
        const exports: ExportRecord[] = [
            { id: 1, status: "completed", created_at: "2026-04-20T10:00:00Z" },
            { id: 2, status: "queued", created_at: "2026-04-21T08:00:00Z" },
        ];
        mockListExports.mockResolvedValueOnce({ data: exports });
        renderSection();
        await waitFor(() =>
            expect(screen.getByText(/concluída/i)).toBeInTheDocument(),
        );
        expect(screen.getByText(/na fila/i)).toBeInTheDocument();
    });

    it("shows email confirmation banner after successful deletion request", async () => {
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
            expect(
                screen.getByText(/enviamos um link de confirmação/i),
            ).toBeInTheDocument(),
        );
        expect(screen.queryByRole("dialog")).not.toBeInTheDocument();
    });

    it("closes the deletion modal when the cancel button is clicked", async () => {
        renderSection();
        fireEvent.click(
            screen.getByRole("button", { name: /excluir minha conta/i }),
        );
        expect(
            screen.getByRole("dialog"),
        ).toBeInTheDocument();
        // The cancel button inside the modal
        const cancelButtons = screen.getAllByRole("button", {
            name: /cancelar/i,
        });
        // The cancel button inside the dialog (last one, inside the modal)
        fireEvent.click(cancelButtons[cancelButtons.length - 1]);
        await waitFor(() =>
            expect(
                screen.queryByRole("dialog"),
            ).not.toBeInTheDocument(),
        );
    });
});
