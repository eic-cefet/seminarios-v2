import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

// Pages
import Home from './pages/Home';
import Login from './pages/Login';
import Register from './pages/Register';
import AuthCallback from './pages/AuthCallback';
import Subjects from './pages/Subjects';
import SubjectSeminars from './pages/SubjectSeminars';
import Presentations from './pages/Presentations';
import SeminarDetails from './pages/SeminarDetails';
import Workshops from './pages/Workshops';
import WorkshopDetails from './pages/WorkshopDetails';

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
        <QueryClientProvider client={queryClient}>
            <BrowserRouter>
                <Routes>
                    <Route path="/" element={<Home />} />
                    <Route path="/login" element={<Login />} />
                    <Route path="/cadastro" element={<Register />} />
                    <Route path="/auth/callback" element={<AuthCallback />} />
                    <Route path="/disciplinas" element={<Subjects />} />
                    <Route path="/disciplina/:id" element={<SubjectSeminars />} />
                    <Route path="/apresentacoes" element={<Presentations />} />
                    <Route path="/seminario/:slug" element={<SeminarDetails />} />
                    <Route path="/workshops" element={<Workshops />} />
                    <Route path="/workshop/:id" element={<WorkshopDetails />} />
                </Routes>
            </BrowserRouter>
        </QueryClientProvider>
    );
}

const container = document.getElementById('app');
if (container) {
    createRoot(container).render(
        <StrictMode>
            <App />
        </StrictMode>
    );
}
