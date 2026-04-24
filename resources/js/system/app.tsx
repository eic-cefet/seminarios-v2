import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { HelmetProvider } from "react-helmet-async";
import { ErrorBoundary } from "@shared/components/ErrorBoundary";
import { ROUTES } from "@shared/config/routes";
import { AuthProvider } from "@shared/contexts/AuthContext";
import { usePageTracking } from "@shared/hooks/usePageTracking";

import Home from "./pages/Home";
import Login from "./pages/Login";
import TwoFactorChallenge from "./pages/TwoFactorChallenge";
import Register from "./pages/Register";
import AuthCallback from "./pages/AuthCallback";
import Profile from "./pages/Profile";
import Certificates from "./pages/Certificates";
import AlertPreferences from "./pages/AlertPreferences";
import Subjects from "./pages/Subjects";
import SubjectSeminars from "./pages/SubjectSeminars";
import Presentations from "./pages/Presentations";
import SeminarDetails from "./pages/SeminarDetails";
import Workshops from "./pages/Workshops";
import WorkshopDetails from "./pages/WorkshopDetails";
import Presence from "./pages/Presence";
import Evaluations from "./pages/Evaluations";
import BugReport from "./pages/BugReport";
import ForgotPassword from "./pages/ForgotPassword";
import ResetPassword from "./pages/ResetPassword";
import NotFound from "./pages/NotFound";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 1000 * 60 * 5,
            retry: 1,
        },
    },
});

function AppRoutes() {
    usePageTracking();

    return (
        <Routes>
            <Route path={ROUTES.SYSTEM.HOME} element={<Home />} />
            <Route path={ROUTES.SYSTEM.LOGIN} element={<Login />} />
            <Route path={ROUTES.SYSTEM.TWO_FACTOR_CHALLENGE} element={<TwoFactorChallenge />} />
            <Route path={ROUTES.SYSTEM.REGISTER} element={<Register />} />
            <Route path={ROUTES.SYSTEM.FORGOT_PASSWORD} element={<ForgotPassword />} />
            <Route path={ROUTES.SYSTEM.RESET_PASSWORD} element={<ResetPassword />} />
            <Route path={ROUTES.SYSTEM.AUTH_CALLBACK} element={<AuthCallback />} />
            <Route path={ROUTES.SYSTEM.PROFILE} element={<Profile />} />
            <Route path={ROUTES.SYSTEM.CERTIFICATES} element={<Certificates />} />
            <Route path={ROUTES.SYSTEM.ALERT_PREFERENCES} element={<AlertPreferences />} />
            <Route path={ROUTES.SYSTEM.SUBJECTS} element={<Subjects />} />
            <Route path={ROUTES.SYSTEM.SUBJECT_DETAILS_PATTERN} element={<SubjectSeminars />} />
            <Route path={ROUTES.SYSTEM.PRESENTATIONS} element={<Presentations />} />
            <Route path={ROUTES.SYSTEM.SEMINAR_DETAILS_PATTERN} element={<SeminarDetails />} />
            <Route path={ROUTES.SYSTEM.WORKSHOPS} element={<Workshops />} />
            <Route path={ROUTES.SYSTEM.WORKSHOP_DETAILS_PATTERN} element={<WorkshopDetails />} />
            <Route path={ROUTES.SYSTEM.PRESENCE_PATTERN} element={<Presence />} />
            <Route path={ROUTES.SYSTEM.EVALUATIONS} element={<Evaluations />} />
            <Route path={ROUTES.SYSTEM.BUG_REPORT} element={<BugReport />} />
            <Route path="*" element={<NotFound />} />
        </Routes>
    );
}

function App() {
    return (
        <ErrorBoundary>
            <HelmetProvider>
                <QueryClientProvider client={queryClient}>
                    <AuthProvider>
                        <BrowserRouter basename={app.ROUTER_BASE || undefined}>
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
