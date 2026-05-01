import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { UserDialogForm } from "./UserDialogForm";

describe("UserDialogForm", () => {
    it("renders create-mode fields when editingUser is null", () => {
        const queryClient = new QueryClient();

        render(
            <QueryClientProvider client={queryClient}>
                <UserDialogForm
                    editingUser={null}
                    isSubmitting={false}
                    onCreate={vi.fn()}
                    onUpdate={vi.fn()}
                    onCancel={vi.fn()}
                />
            </QueryClientProvider>,
        );

        // The create-mode form has an empty name input and a required password field.
        expect(screen.getByLabelText(/nome/i)).toHaveValue("");
        expect(screen.getByLabelText(/senha/i)).toBeInTheDocument();
    });
});
