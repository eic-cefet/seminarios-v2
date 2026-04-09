type EventParams = Record<string, string | number | boolean | undefined>;

declare global {
    interface Window {
        gtag?: (...args: unknown[]) => void;
    }
}

/**
 * Google Analytics 4 utility.
 * All methods are no-ops when GA_MEASUREMENT_ID is not configured.
 */
export const analytics = {
    /**
     * Track a pageview event.
     * Called automatically by usePageTracking hook on route changes.
     */
    pageview(path: string, title?: string) {
        if (window.gtag && app.GA_MEASUREMENT_ID) {
            window.gtag("event", "page_view", {
                page_path: path,
                page_title: title,
            });
        }
    },

    /**
     * Track a custom event.
     * @param name - Event name (snake_case, e.g., 'seminar_register')
     * @param params - Optional event parameters
     */
    event(name: string, params?: EventParams) {
        if (window.gtag && app.GA_MEASUREMENT_ID) {
            window.gtag("event", name, params);
        }
    },
};
