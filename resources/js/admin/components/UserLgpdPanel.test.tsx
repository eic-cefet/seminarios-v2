import { describe, it, expect, vi } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { UserLgpdPanel } from "./UserLgpdPanel";

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const mockShow = vi.fn((_id?: number): Promise<any> =>
    Promise.resolve({
        data: {
            consents: [] as any[],
            data_export_requests: [] as any[],
            anonymization_requested_at: null as string | null,
            anonymized_at: null as string | null,
        },
    }),
);
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const mockExport = vi.fn((_id?: number): Promise<any> =>
    Promise.resolve({ data: { data_export_request_id: 1 } }),
);
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const mockAnonymize = vi.fn((_id?: number, _reason?: string): Promise<any> =>
    Promise.resolve({ message: "ok" }),
);

vi.mock("../api/adminClient", () => ({
    adminLgpdApi: {
        show: (id: number) => mockShow(id),
        export: (id: number) => mockExport(id),
        anonymize: (id: number, reason: string) => mockAnonymize(id, reason),
    },
}));

function renderPanel() {
    const client = new QueryClient({
        defaultOptions: { queries: { retry: false } },
    });
    return render(
        <QueryClientProvider client={client}>
            <UserLgpdPanel userId={42} />
        </QueryClientProvider>,
    );
}

describe("UserLgpdPanel", () => {
    beforeEach(() => {
        mockShow.mockClear();
        mockExport.mockClear();
        mockAnonymize.mockClear();
    });

    it("triggers export on behalf of user", async () => {
        renderPanel();
        await waitFor(() =>
            expect(
                screen.getByRole("button", { name: /gerar exporta/i }),
            ).toBeInTheDocument(),
        );
        fireEvent.click(screen.getByRole("button", { name: /gerar exporta/i }));
        await waitFor(() => expect(mockExport).toHaveBeenCalledWith(42));
    });

    it("requires a reason (at least 5 chars) to anonymize", async () => {
        renderPanel();
        await waitFor(() =>
            expect(
                screen.getByRole("button", { name: /anonimizar agora/i }),
            ).toBeInTheDocument(),
        );
        fireEvent.click(
            screen.getByRole("button", { name: /anonimizar agora/i }),
        );
        const input = await screen.findByLabelText(/motivo/i);
        fireEvent.change(input, { target: { value: "ANPD #123" } });
        fireEvent.click(
            screen.getByRole("button", { name: /confirmar anonimiza/i }),
        );
        await waitFor(() =>
            expect(mockAnonymize).toHaveBeenCalledWith(42, "ANPD #123"),
        );
    });

    it("renders empty-state messages when no data", async () => {
        renderPanel();
        await waitFor(() =>
            expect(
                screen.getAllByText(/nenhum/i).length,
            ).toBeGreaterThanOrEqual(1),
        );
    });

    it("shows loading state initially", async () => {
        let resolveShow: (value: any) => void;
        mockShow.mockImplementationOnce(
            () => new Promise((resolve) => { resolveShow = resolve; }),
        );

        renderPanel();
        expect(screen.getByText(/carregando/i)).toBeInTheDocument();

        resolveShow!({
            data: {
                consents: [],
                data_export_requests: [],
                anonymization_requested_at: null,
                anonymized_at: null,
            },
        });
    });

    it("shows error state when API fails", async () => {
        mockShow.mockRejectedValueOnce(new Error("Network error"));

        renderPanel();
        await waitFor(() =>
            expect(
                screen.getByText(/erro ao carregar/i),
            ).toBeInTheDocument(),
        );
    });

    it("renders consent records when present", async () => {
        mockShow.mockResolvedValueOnce({
            data: {
                consents: [
                    {
                        type: "privacy_policy",
                        granted: true,
                        version: "1.0",
                        source: "banner",
                        created_at: "2026-01-15T10:00:00Z",
                    },
                ] as any[],
                data_export_requests: [] as any[],
                anonymization_requested_at: null as string | null,
                anonymized_at: null as string | null,
            },
        });

        renderPanel();
        await waitFor(() =>
            expect(screen.getByText("privacy_policy")).toBeInTheDocument(),
        );
        expect(screen.getByText("concedido")).toBeInTheDocument();
        expect(screen.getByText("1.0")).toBeInTheDocument();
    });

    it("renders data export requests when present", async () => {
        mockShow.mockResolvedValueOnce({
            data: {
                consents: [] as any[],
                data_export_requests: [
                    {
                        id: 7,
                        status: "completed",
                        created_at: "2026-02-01T00:00:00Z",
                        completed_at: "2026-02-01T01:00:00Z",
                        expires_at: "2026-03-01T00:00:00Z",
                    },
                ] as any[],
                anonymization_requested_at: null as string | null,
                anonymized_at: null as string | null,
            },
        });

        renderPanel();
        await waitFor(() =>
            expect(screen.getByText(/#7/)).toBeInTheDocument(),
        );
        expect(screen.getByText(/concluída/i)).toBeInTheDocument();
    });

    it("shows pending deletion message when anonymization_requested_at is set", async () => {
        mockShow.mockResolvedValueOnce({
            data: {
                consents: [] as any[],
                data_export_requests: [] as any[],
                anonymization_requested_at: "2026-04-01T00:00:00Z" as string | null,
                anonymized_at: null as string | null,
            },
        });

        renderPanel();
        await waitFor(() =>
            expect(screen.getByText(/exclusão pendente/i)).toBeInTheDocument(),
        );
    });

    it("shows anonymized message and disables buttons when account is anonymized", async () => {
        mockShow.mockResolvedValueOnce({
            data: {
                consents: [] as any[],
                data_export_requests: [] as any[],
                anonymization_requested_at: "2026-04-01T00:00:00Z" as string | null,
                anonymized_at: "2026-04-08T00:00:00Z" as string | null,
            },
        });

        renderPanel();
        await waitFor(() =>
            expect(
                screen.getByText(/conta anonimizada/i),
            ).toBeInTheDocument(),
        );
        expect(
            screen.getByRole("button", { name: /gerar exporta/i }),
        ).toBeDisabled();
        expect(
            screen.getByRole("button", { name: /anonimizar agora/i }),
        ).toBeDisabled();
    });

    it("cancels the anonymization dialog", async () => {
        renderPanel();
        await waitFor(() =>
            expect(
                screen.getByRole("button", { name: /anonimizar agora/i }),
            ).toBeInTheDocument(),
        );
        fireEvent.click(
            screen.getByRole("button", { name: /anonimizar agora/i }),
        );
        expect(screen.getByRole("dialog")).toBeInTheDocument();

        fireEvent.click(screen.getByRole("button", { name: /cancelar/i }));
        await waitFor(() =>
            expect(screen.queryByRole("dialog")).not.toBeInTheDocument(),
        );
    });

    it("confirm button is disabled when reason is shorter than 5 chars", async () => {
        renderPanel();
        await waitFor(() =>
            expect(
                screen.getByRole("button", { name: /anonimizar agora/i }),
            ).toBeInTheDocument(),
        );
        fireEvent.click(
            screen.getByRole("button", { name: /anonimizar agora/i }),
        );
        const input = await screen.findByLabelText(/motivo/i);
        fireEvent.change(input, { target: { value: "ab" } });
        expect(
            screen.getByRole("button", { name: /confirmar anonimiza/i }),
        ).toBeDisabled();
    });

    it("shows form error when anonymize mutation fails", async () => {
        mockAnonymize.mockRejectedValueOnce(new Error("Server error"));

        renderPanel();
        await waitFor(() =>
            expect(
                screen.getByRole("button", { name: /anonimizar agora/i }),
            ).toBeInTheDocument(),
        );
        fireEvent.click(
            screen.getByRole("button", { name: /anonimizar agora/i }),
        );
        const input = await screen.findByLabelText(/motivo/i);
        fireEvent.change(input, { target: { value: "ANPD ticket error" } });
        fireEvent.click(
            screen.getByRole("button", { name: /confirmar anonimiza/i }),
        );
        await waitFor(() =>
            expect(screen.getByText("Server error")).toBeInTheDocument(),
        );
    });
});
