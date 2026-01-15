import { useState } from 'react';
import { Link } from 'react-router-dom';
import * as Dialog from '@radix-ui/react-dialog';
import * as Label from '@radix-ui/react-label';
import { X } from 'lucide-react';
import { cn } from '@shared/lib/utils';

interface LoginModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function LoginModal({ open, onOpenChange }: LoginModalProps) {
    const [view, setView] = useState<'login' | 'forgot'>('login');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [forgotEmail, setForgotEmail] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [forgotSuccess, setForgotSuccess] = useState(false);

    const handleLogin = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            // TODO: Implement actual login
            console.log('Login:', { email, password });
            await new Promise((resolve) => setTimeout(resolve, 1000));
            onOpenChange(false);
        } catch {
            setError('E-mail ou senha inválidos');
        } finally {
            setLoading(false);
        }
    };

    const handleForgotPassword = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            // TODO: Implement actual forgot password
            console.log('Forgot password:', { forgotEmail });
            await new Promise((resolve) => setTimeout(resolve, 1000));
            setForgotSuccess(true);
        } catch {
            setError('Erro ao enviar e-mail de recuperação');
        } finally {
            setLoading(false);
        }
    };

    const handleSocialLogin = (provider: 'google' | 'github') => {
        // TODO: Implement OAuth redirect
        window.location.href = `/auth/${provider}`;
    };

    const resetModal = () => {
        setView('login');
        setEmail('');
        setPassword('');
        setForgotEmail('');
        setError(null);
        setForgotSuccess(false);
    };

    return (
        <Dialog.Root
            open={open}
            onOpenChange={(newOpen) => {
                if (!newOpen) resetModal();
                onOpenChange(newOpen);
            }}
        >
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" />
                <Dialog.Content className="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-lg shadow-xl p-6 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%]">
                    <Dialog.Close asChild>
                        <button
                            className="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-white transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-offset-2"
                            aria-label="Fechar"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </Dialog.Close>

                    {view === 'login' ? (
                        <>
                            <Dialog.Title className="text-xl font-semibold text-gray-900">
                                Entrar
                            </Dialog.Title>
                            <Dialog.Description className="mt-1 text-sm text-gray-500">
                                Entre com sua conta para acessar o sistema
                            </Dialog.Description>

                            {/* Social Login Buttons */}
                            <div className="mt-6 space-y-3">
                                <button
                                    type="button"
                                    onClick={() => handleSocialLogin('google')}
                                    className="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                                >
                                    <GoogleIcon className="h-5 w-5" />
                                    Continuar com Google
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleSocialLogin('github')}
                                    className="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                                >
                                    <GithubIcon className="h-5 w-5" />
                                    Continuar com GitHub
                                </button>
                            </div>

                            <div className="relative my-6">
                                <div className="absolute inset-0 flex items-center">
                                    <div className="w-full border-t border-gray-200" />
                                </div>
                                <div className="relative flex justify-center text-sm">
                                    <span className="bg-white px-2 text-gray-500">ou</span>
                                </div>
                            </div>

                            {/* Email/Password Form */}
                            <form onSubmit={handleLogin} className="space-y-4">
                                {error && (
                                    <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                        {error}
                                    </div>
                                )}

                                <div>
                                    <Label.Root
                                        htmlFor="email"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        E-mail
                                    </Label.Root>
                                    <input
                                        id="email"
                                        type="email"
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                        required
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                        placeholder="seu@email.com"
                                    />
                                </div>

                                <div>
                                    <div className="flex items-center justify-between">
                                        <Label.Root
                                            htmlFor="password"
                                            className="block text-sm font-medium text-gray-700"
                                        >
                                            Senha
                                        </Label.Root>
                                        <button
                                            type="button"
                                            onClick={() => setView('forgot')}
                                            className="text-sm text-primary-600 hover:text-primary-700"
                                        >
                                            Esqueci minha senha
                                        </button>
                                    </div>
                                    <input
                                        id="password"
                                        type="password"
                                        value={password}
                                        onChange={(e) => setPassword(e.target.value)}
                                        required
                                        className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                        placeholder="••••••••"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    disabled={loading}
                                    className={cn(
                                        'w-full rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors',
                                        loading && 'opacity-70 cursor-not-allowed'
                                    )}
                                >
                                    {loading ? 'Entrando...' : 'Entrar'}
                                </button>
                            </form>

                            <p className="mt-4 text-center text-sm text-gray-500">
                                Não tem uma conta?{' '}
                                <Link
                                    to="/cadastro"
                                    onClick={() => onOpenChange(false)}
                                    className="font-medium text-primary-600 hover:text-primary-700"
                                >
                                    Cadastre-se
                                </Link>
                            </p>
                        </>
                    ) : (
                        <>
                            <Dialog.Title className="text-xl font-semibold text-gray-900">
                                Recuperar senha
                            </Dialog.Title>
                            <Dialog.Description className="mt-1 text-sm text-gray-500">
                                Digite seu e-mail para receber um link de recuperação
                            </Dialog.Description>

                            {forgotSuccess ? (
                                <div className="mt-6 space-y-4">
                                    <div className="rounded-md bg-green-50 p-4 text-sm text-green-700">
                                        Se o e-mail informado estiver cadastrado, você receberá um
                                        link para redefinir sua senha.
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setView('login');
                                            setForgotSuccess(false);
                                        }}
                                        className="w-full rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                                    >
                                        Voltar para login
                                    </button>
                                </div>
                            ) : (
                                <form onSubmit={handleForgotPassword} className="mt-6 space-y-4">
                                    {error && (
                                        <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                                            {error}
                                        </div>
                                    )}

                                    <div>
                                        <Label.Root
                                            htmlFor="forgot-email"
                                            className="block text-sm font-medium text-gray-700"
                                        >
                                            E-mail
                                        </Label.Root>
                                        <input
                                            id="forgot-email"
                                            type="email"
                                            value={forgotEmail}
                                            onChange={(e) => setForgotEmail(e.target.value)}
                                            required
                                            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                            placeholder="seu@email.com"
                                        />
                                    </div>

                                    <div className="flex gap-3">
                                        <button
                                            type="button"
                                            onClick={() => setView('login')}
                                            className="flex-1 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                                        >
                                            Voltar
                                        </button>
                                        <button
                                            type="submit"
                                            disabled={loading}
                                            className={cn(
                                                'flex-1 rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors',
                                                loading && 'opacity-70 cursor-not-allowed'
                                            )}
                                        >
                                            {loading ? 'Enviando...' : 'Enviar'}
                                        </button>
                                    </div>
                                </form>
                            )}
                        </>
                    )}
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}

function GoogleIcon({ className }: { className?: string }) {
    return (
        <svg className={className} viewBox="0 0 24 24">
            <path
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                fill="#4285F4"
            />
            <path
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                fill="#34A853"
            />
            <path
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                fill="#FBBC05"
            />
            <path
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                fill="#EA4335"
            />
        </svg>
    );
}

function GithubIcon({ className }: { className?: string }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
        </svg>
    );
}
