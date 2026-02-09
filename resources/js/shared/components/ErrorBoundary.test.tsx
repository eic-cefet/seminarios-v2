import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { ErrorBoundary } from "./ErrorBoundary";

function ThrowingComponent({ shouldThrow }: { shouldThrow: boolean }) {
    if (shouldThrow) {
        throw new Error("Test error");
    }
    return <div>Child content</div>;
}

describe("ErrorBoundary", () => {
    beforeEach(() => {
        vi.spyOn(console, "error").mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it("renders children when no error occurs", () => {
        render(
            <ErrorBoundary>
                <ThrowingComponent shouldThrow={false} />
            </ErrorBoundary>,
        );

        expect(screen.getByText("Child content")).toBeInTheDocument();
    });

    it("renders default fallback when an error occurs", () => {
        render(
            <ErrorBoundary>
                <ThrowingComponent shouldThrow={true} />
            </ErrorBoundary>,
        );

        expect(screen.getByText("Algo deu errado")).toBeInTheDocument();
        expect(
            screen.getByText(/Ocorreu um erro inesperado/),
        ).toBeInTheDocument();
        expect(
            screen.getByRole("button", { name: "Recarregar pagina" }),
        ).toBeInTheDocument();
    });

    it("renders custom fallback when provided", () => {
        render(
            <ErrorBoundary fallback={<div>Custom error fallback</div>}>
                <ThrowingComponent shouldThrow={true} />
            </ErrorBoundary>,
        );

        expect(screen.getByText("Custom error fallback")).toBeInTheDocument();
        expect(screen.queryByText("Algo deu errado")).not.toBeInTheDocument();
    });

    it("calls console.error when an error is caught", () => {
        render(
            <ErrorBoundary>
                <ThrowingComponent shouldThrow={true} />
            </ErrorBoundary>,
        );

        expect(console.error).toHaveBeenCalled();
    });

    it("reloads page when reload button is clicked", async () => {
        const reloadMock = vi.fn();
        Object.defineProperty(window, "location", {
            value: { reload: reloadMock },
            writable: true,
        });

        render(
            <ErrorBoundary>
                <ThrowingComponent shouldThrow={true} />
            </ErrorBoundary>,
        );

        await userEvent.click(
            screen.getByRole("button", { name: "Recarregar pagina" }),
        );
        expect(reloadMock).toHaveBeenCalled();
    });
});
