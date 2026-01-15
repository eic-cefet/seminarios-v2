import { useEffect, useState, useRef } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import { Layout } from '../components/Layout';
import { useAuth } from '@shared/contexts/AuthContext';
import { getErrorMessage } from '@shared/lib/errors';

export default function AuthCallback() {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const { exchangeCode, user } = useAuth();
    const [error, setError] = useState<string | null>(null);
    const [exchangeComplete, setExchangeComplete] = useState(false);
    const hasRun = useRef(false);

    // Navigate once exchange is complete and user is set
    useEffect(() => {
        if (exchangeComplete && user) {
            navigate('/', { replace: true });
        }
    }, [exchangeComplete, user, navigate]);

    useEffect(() => {
        if (hasRun.current) return;
        hasRun.current = true;

        const code = searchParams.get('code');
        const errorParam = searchParams.get('error');

        if (errorParam) {
            setError('Ocorreu um erro durante a autenticação. Tente novamente.');
            return;
        }

        if (!code) {
            setError('Código de autenticação não encontrado.');
            return;
        }

        handleExchange(code);
    }, [searchParams]);

    const handleExchange = async (code: string) => {
        try {
            await exchangeCode(code);
            setExchangeComplete(true);
        } catch (err) {
            setError(getErrorMessage(err));
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
                            Erro na autenticação
                        </h1>
                        <p className="mt-2 text-gray-500">{error}</p>
                        <button
                            onClick={() => navigate('/login')}
                            className="mt-6 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 cursor-pointer"
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
