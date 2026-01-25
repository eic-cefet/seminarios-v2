import { useEffect } from "react";
import { useLocation } from "react-router-dom";
import { analytics } from "../lib/analytics";

/**
 * Hook that tracks pageviews on route changes.
 * Add this to your App component inside the Router context.
 */
export function usePageTracking() {
    const location = useLocation();

    useEffect(() => {
        analytics.pageview(
            location.pathname + location.search,
            document.title,
        );
    }, [location]);
}
