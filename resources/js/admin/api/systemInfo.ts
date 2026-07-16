import { fetchAdminApi, getCsrfCookie } from "./_base";

export interface AdminSystemInfo {
    actions: {
        database_reset_available: boolean;
    };
    runtime: {
        php_version: string;
        laravel_version: string;
        app_version: string;
        environment: string;
        debug: boolean;
        timezone: string;
        locale: string;
    };
    server: {
        os_family: string;
        os_release: string;
        hostname: string;
        server_software: string;
        sapi: string;
        architecture: string;
    };
    memory: {
        limit_bytes: number;
        current_bytes: number;
        peak_bytes: number;
    };
    database: {
        driver: string;
        database: string | null;
        host: string | null;
        version: string;
    };
    drivers: {
        cache: string;
        queue: string;
        session: string;
        mail: string;
        filesystem: string;
    };
    storage: {
        free_bytes: number;
        total_bytes: number;
    };
    extensions: string[];
    php_config: {
        max_execution_time: number;
        post_max_size: string;
        upload_max_filesize: string;
        opcache_enabled: boolean;
    };
    scheduler: Array<{
        command: string;
        description: string | null;
        expression: string;
        timezone: string;
        without_overlapping: boolean;
        on_one_server: boolean;
        next_run_at: string | null;
    }>;
}

export const systemInfoApi = {
    get: () => fetchAdminApi<{ data: AdminSystemInfo }>("/system/info"),
    resetDatabase: async (confirmation: string) => {
        await getCsrfCookie();

        return fetchAdminApi<{ message: string }>("/system/database/reset", {
            method: "POST",
            body: JSON.stringify({ confirmation }),
        });
    },
};
