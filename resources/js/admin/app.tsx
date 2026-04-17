import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { HelmetProvider } from "react-helmet-async";
import { ErrorBoundary } from "@shared/components/ErrorBoundary";
import { ROUTES } from "@shared/config/routes";
import { AuthProvider } from "@shared/contexts/AuthContext";
import { usePageTracking } from "@shared/hooks/usePageTracking";
import { AdminLayout } from "./components/layout/AdminLayout";
import Dashboard from "./pages/Dashboard";
import LocationList from "./pages/locations/LocationList";
import SubjectList from "./pages/subjects/SubjectList";
import UserList from "./pages/users/UserList";
import RegistrationList from "./pages/registrations/RegistrationList";
import { SeminarList, SeminarForm } from "./pages/seminars";
import { WorkshopList } from "./pages/workshops";
import AuditLogReport from "./pages/reports/AuditLogReport";
import FeedbackInsights from "./pages/reports/FeedbackInsights";
import SemestralReport from "./pages/reports/SemestralReport";
import ApiTokenList from "./pages/api-tokens/ApiTokenList";
import NotFound from "./pages/NotFound";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 1000 * 60 * 5,
            retry: 1,
        },
    },
});

export function AppRoutes() {
    usePageTracking();

    return (
        <Routes>
            <Route element={<AdminLayout />}>
                <Route path={ROUTES.ADMIN.DASHBOARD} element={<Dashboard />} />
                <Route path={ROUTES.ADMIN.USERS} element={<UserList />} />
                <Route path={ROUTES.ADMIN.LOCATIONS} element={<LocationList />} />
                <Route path={ROUTES.ADMIN.SUBJECTS} element={<SubjectList />} />
                <Route path={ROUTES.ADMIN.SEMINARS} element={<SeminarList />} />
                <Route path={ROUTES.ADMIN.SEMINAR_NEW} element={<SeminarForm />} />
                <Route path={ROUTES.ADMIN.SEMINAR_EDIT_PATTERN} element={<SeminarForm />} />
                <Route path={ROUTES.ADMIN.WORKSHOPS} element={<WorkshopList />} />
                <Route path={ROUTES.ADMIN.REGISTRATIONS} element={<RegistrationList />} />
                <Route path={ROUTES.ADMIN.API_TOKENS} element={<ApiTokenList />} />
                <Route path={ROUTES.ADMIN.REPORTS_SEMESTRAL} element={<SemestralReport />} />
                <Route path={ROUTES.ADMIN.REPORTS_FEEDBACK} element={<FeedbackInsights />} />
                <Route path={ROUTES.ADMIN.REPORTS_AUDIT_LOGS} element={<AuditLogReport />} />
                <Route path="*" element={<NotFound />} />
            </Route>
        </Routes>
    );
}

export function App() {
    return (
        <ErrorBoundary>
            <HelmetProvider>
                <QueryClientProvider client={queryClient}>
                    <AuthProvider>
                        <BrowserRouter
                            basename={`${app.ROUTER_BASE || ""}/admin`}
                        >
                            <AppRoutes />
                        </BrowserRouter>
                    </AuthProvider>
                </QueryClientProvider>
            </HelmetProvider>
        </ErrorBoundary>
    );
}

const container = document.getElementById("app");
if (container) {
    createRoot(container).render(
        <StrictMode>
            <App />
        </StrictMode>,
    );
}
