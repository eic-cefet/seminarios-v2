import { buildQueryString, fetchAdminApi } from "./_base";

export interface AuditLogSummary {
    total: number;
    manual_count: number;
    system_count: number;
    top_events: Record<string, number>;
}

export const auditLogsApi = {
    summary: (params?: { days?: number }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<{ data: AuditLogSummary }>(
            `/audit-logs/summary${qs}`,
        );
    },

    export: (params?: {
        days?: number;
        event_type?: string;
        event_name?: string;
        search?: string;
    }) => {
        const qs = buildQueryString(params ?? {});
        return fetchAdminApi<{ message: string }>(
            `/audit-logs/export${qs}`,
        );
    },

    eventNames: () =>
        fetchAdminApi<{ data: string[] }>("/audit-logs/event-names"),
};
