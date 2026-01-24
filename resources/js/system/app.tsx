import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { HelmetProvider } from "react-helmet-async";
import { AuthProvider } from "@shared/contexts/AuthContext";

// Pages
import Home from "./pages/Home";
import Login from "./pages/Login";
import Register from "./pages/Register";
import AuthCallback from "./pages/AuthCallback";
import Profile from "./pages/Profile";
import Certificates from "./pages/Certificates";
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
                    <BrowserRouter>
                        <Routes>
                            <Route path="/" element={<Home />} />
                            <Route path="/login" element={<Login />} />
                            <Route path="/cadastro" element={<Register />} />
                            <Route
                                path="/recuperar-senha"
                                element={<ForgotPassword />}
                            />
                            <Route
                                path="/redefinir-senha"
                                element={<ResetPassword />}
                            />
                            <Route
                                path="/auth/callback"
                                element={<AuthCallback />}
                            />
                            <Route path="/perfil" element={<Profile />} />
                            <Route
                                path="/certificados"
                                element={<Certificates />}
                            />
                            <Route path="/topicos" element={<Subjects />} />
                            <Route
                                path="/topico/:id"
                                element={<SubjectSeminars />}
                            />
                            <Route
                                path="/apresentacoes"
                                element={<Presentations />}
                            />
                            <Route
                                path="/seminario/:slug"
                                element={<SeminarDetails />}
                            />
                            <Route path="/workshops" element={<Workshops />} />
                            <Route
                                path="/workshop/:id"
                                element={<WorkshopDetails />}
                            />
                            <Route path="/p/:uuid" element={<Presence />} />
                            <Route path="/avaliar" element={<Evaluations />} />
                            <Route
                                path="/reportar-bug"
                                element={<BugReport />}
                            />
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
