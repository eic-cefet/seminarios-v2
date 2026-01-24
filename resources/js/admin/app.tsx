import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { HelmetProvider } from "react-helmet-async";
import { AuthProvider } from "@shared/contexts/AuthContext";
import { AdminLayout } from "./components/layout/AdminLayout";
import Dashboard from "./pages/Dashboard";
import LocationList from "./pages/locations/LocationList";
import SubjectList from "./pages/subjects/SubjectList";
import UserList from "./pages/users/UserList";
import RegistrationList from "./pages/registrations/RegistrationList";
import { SeminarList, SeminarForm } from "./pages/seminars";
import { WorkshopList } from "./pages/workshops";
import SemestralReport from "./pages/reports/SemestralReport";
import NotFound from "./pages/NotFound";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 1000 * 60 * 5,
            retry: 1,
        },
    },
});

function App() {
    return (
        <HelmetProvider>
            <QueryClientProvider client={queryClient}>
                <AuthProvider>
                    <BrowserRouter basename="/admin">
                        <Routes>
                            <Route element={<AdminLayout />}>
                                <Route path="/" element={<Dashboard />} />
                                <Route path="/users" element={<UserList />} />
                                <Route
                                    path="/locations"
                                    element={<LocationList />}
                                />
                                <Route
                                    path="/subjects"
                                    element={<SubjectList />}
                                />
                                <Route
                                    path="/seminars"
                                    element={<SeminarList />}
                                />
                                <Route
                                    path="/seminars/new"
                                    element={<SeminarForm />}
                                />
                                <Route
                                    path="/seminars/:id/edit"
                                    element={<SeminarForm />}
                                />
                                <Route
                                    path="/workshops"
                                    element={<WorkshopList />}
                                />
                                <Route
                                    path="/registrations"
                                    element={<RegistrationList />}
                                />
                                <Route
                                    path="/reports/semestral"
                                    element={<SemestralReport />}
                                />
                                <Route path="*" element={<NotFound />} />
                            </Route>
                        </Routes>
                    </BrowserRouter>
                </AuthProvider>
            </QueryClientProvider>
        </HelmetProvider>
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
