import { useEffect, useState } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import { Layout } from '../components/Layout';

export default function AuthCallback() {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const code = searchParams.get('code');
        const errorParam = searchParams.get('error');

        if (errorParam) {
            setError('Ocorreu um erro durante a autenticacao. Tente novamente.');
            return;
        }

        if (!code) {
            setError('Codigo de autenticacao nao encontrado.');
            return;
        }

        // Exchange code for session
        exchangeCode(code);
    }, [searchParams]);

    const exchangeCode = async (code: string) => {
        try {
            const response = await fetch('/api/auth/exchange', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                credentials: 'include', // Important for cookies
                body: JSON.stringify({ code }),
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'Falha na autenticacao');
            }

            // Success - redirect to home
            // The session cookie is automatically set by Laravel
            navigate('/', { replace: true });
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Erro ao autenticar');
        }
    };

    if (error) {
        return (
            <Layout>
                <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center py-12 px-4">
                    <div className="text-center">
                        <div className="mx-auto h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
                            <span className="text-2xl">!</span>
                        </div>
                        <h1 className="mt-4 text-xl font-semibold text-gray-900">
                            Erro na autenticacao
                        </h1>
                        <p className="mt-2 text-gray-500">{error}</p>
                        <button
                            onClick={() => navigate('/login')}
                            className="mt-6 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
                        >
                            Voltar para login
                        </button>
                    </div>
                </div>
            </Layout>
        );
    }

    return (
        <Layout>
            <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center py-12 px-4">
                <div className="text-center">
                    <Loader2 className="mx-auto h-8 w-8 animate-spin text-primary-600" />
                    <p className="mt-4 text-gray-500">Autenticando...</p>
                </div>
            </div>
        </Layout>
    );
}
