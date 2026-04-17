import { useMutation, useQuery } from "@tanstack/react-query";
import {
    ArrowRight,
    Calendar,
    CheckCircle,
    Home,
    Loader2,
    User,
    XCircle,
} from "lucide-react";
import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { presenceApi } from "@shared/api/client";
import { ROUTES } from "@shared/config/routes";
import { useAuth } from "@shared/contexts/AuthContext";
import { formatDateTimeLong } from "@shared/lib/utils";
import { Layout } from "../components/Layout";
import { LoginModal } from "../components/LoginModal";

export default function Presence() {
    const { uuid } = useParams<{ uuid: string }>();
    const { user } = useAuth();
    const [showLoginModal, setShowLoginModal] = useState(false);
    const [registrationAttempted, setRegistrationAttempted] = useState(false);

    const {
        data: linkData,
        isLoading: isCheckingLink,
        error: linkError,
    } = useQuery({
        queryKey: ["presence-link", uuid],
        queryFn: () => presenceApi.get(uuid!),
        enabled: !!uuid,
    });

    const registerMutation = useMutation({
        mutationFn: () => presenceApi.register(uuid!),
    });

    // Auto-register when user is authenticated and link is valid
    useEffect(() => {
        if (
            user &&
            linkData?.data?.is_valid &&
            !registrationAttempted &&
            !registerMutation.isSuccess &&
            !registerMutation.isError
        ) {
            setRegistrationAttempted(true);
            registerMutation.mutate();
        }
    }, [user, linkData, registrationAttempted, registerMutation]);

    // Show login modal if not authenticated
    useEffect(() => {
        if (!user && linkData?.data?.is_valid && !showLoginModal) {
            setShowLoginModal(true);
        }
    }, [user, linkData, showLoginModal]);

    const seminar = linkData?.data?.seminar;

    if (isCheckingLink) {
        return (
            <Layout>
                <div className="flex-1 flex items-center justify-center py-16">
                    <div className="text-center space-y-4">
                        <Loader2 className="h-12 w-12 animate-spin mx-auto text-primary-600" />
                        <p className="text-gray-500">Verificando link...</p>
                    </div>
                </div>
            </Layout>
        );
    }

    if (linkError || !linkData?.data?.is_valid) {
        return (
            <Layout>
                <div className="flex-1 flex items-center justify-center py-16">
                    <div className="max-w-md w-full mx-4">
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center space-y-6">
                            <div className="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                                <XCircle className="h-8 w-8 text-red-600" />
                            </div>
                            <div className="space-y-2">
                                <h1 className="text-2xl font-bold text-gray-900">
                                    Link Inválido
                                </h1>
                                <p className="text-gray-500">
                                    {linkError instanceof Error
                                        ? linkError.message || "Link inválido"
                                        : "Este link de presença não é válido ou expirou."}
                                </p>
                            </div>
                            <Link
                                to={ROUTES.SYSTEM.HOME}
                                className="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 font-medium"
                            >
                                <Home className="h-4 w-4" />
                                Voltar para o início
                            </Link>
                        </div>
                    </div>
                </div>
            </Layout>
        );
    }

    if (!user) {
        return (
            <Layout>
                <div className="flex-1 flex items-center justify-center py-16">
                    <div className="max-w-md w-full mx-4">
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center space-y-6">
                            <div className="mx-auto w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                                <User className="h-8 w-8 text-primary-600" />
                            </div>
                            <div className="space-y-2">
                                <h1 className="text-2xl font-bold text-gray-900">
                                    Registrar Presença
                                </h1>
                                <p className="text-gray-500">
                                    Você precisa estar autenticado para
                                    registrar presença.
                                </p>
                            </div>

                            {seminar && (
                                <div className="bg-gray-50 rounded-lg p-4 text-left space-y-2">
                                    <p className="font-semibold text-gray-900">
                                        {seminar.name}
                                    </p>
                                    {seminar.scheduled_at && (
                                        <p className="text-sm text-gray-500 flex items-center gap-2">
                                            <Calendar className="h-4 w-4" />
                                            {formatDateTimeLong(
                                                seminar.scheduled_at,
                                            )}
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="flex flex-col gap-3">
                                <button
                                    onClick={() => setShowLoginModal(true)}
                                    className="w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors"
                                >
                                    Entrar na conta
                                    <ArrowRight className="h-4 w-4" />
                                </button>
                                <Link
                                    to={ROUTES.SYSTEM.REGISTER}
                                    className="text-sm text-gray-500 hover:text-gray-700"
                                >
                                    Não tem conta?{" "}
                                    <span className="text-primary-600 font-medium">
                                        Criar conta
                                    </span>
                                </Link>
                            </div>
                        </div>
                    </div>
                    <LoginModal
                        open={showLoginModal}
                        onOpenChange={setShowLoginModal}
                    />
                </div>
            </Layout>
        );
    }

    if (registerMutation.isPending) {
        return (
            <Layout>
                <div className="flex-1 flex items-center justify-center py-16">
                    <div className="text-center space-y-4">
                        <Loader2 className="h-12 w-12 animate-spin mx-auto text-primary-600" />
                        <p className="text-gray-500">Registrando presença...</p>
                    </div>
                </div>
            </Layout>
        );
    }

    if (registerMutation.isError) {
        return (
            <Layout>
                <div className="flex-1 flex items-center justify-center py-16">
                    <div className="max-w-md w-full mx-4">
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center space-y-6">
                            <div className="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                                <XCircle className="h-8 w-8 text-red-600" />
                            </div>
                            <div className="space-y-2">
                                <h1 className="text-2xl font-bold text-gray-900">
                                    Erro ao Registrar
                                </h1>
                                <p className="text-gray-500">
                                    {registerMutation.error instanceof Error
                                        ? registerMutation.error.message ||
                                          "Erro ao registrar presença"
                                        : "Não foi possível registrar sua presença."}
                                </p>
                            </div>
                            <div className="flex flex-col gap-3">
                                <button
                                    onClick={() => registerMutation.mutate()}
                                    className="w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors"
                                >
                                    Tentar novamente
                                </button>
                                <Link
                                    to={ROUTES.SYSTEM.HOME}
                                    className="inline-flex items-center justify-center gap-2 text-gray-500 hover:text-gray-700 text-sm"
                                >
                                    <Home className="h-4 w-4" />
                                    Voltar para o início
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </Layout>
        );
    }

    if (registerMutation.isSuccess) {
        return (
            <Layout>
                <div className="flex-1 flex items-center justify-center py-16">
                    <div className="max-w-md w-full mx-4">
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center space-y-6">
                            <div className="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                <CheckCircle className="h-8 w-8 text-green-600" />
                            </div>
                            <div className="space-y-2">
                                <h1 className="text-2xl font-bold text-gray-900">
                                    Presença Registrada!
                                </h1>
                                <p className="text-gray-500">
                                    Olá,{" "}
                                    <span className="font-medium text-gray-700">
                                        {user.name}
                                    </span>
                                    ! Sua presença foi registrada com sucesso.
                                </p>
                            </div>

                            {seminar && (
                                <div className="bg-green-50 rounded-lg p-4 text-left space-y-2 border border-green-100">
                                    <p className="font-semibold text-gray-900">
                                        {seminar.name}
                                    </p>
                                    {seminar.scheduled_at && (
                                        <p className="text-sm text-gray-600 flex items-center gap-2">
                                            <Calendar className="h-4 w-4" />
                                            {formatDateTimeLong(
                                                seminar.scheduled_at,
                                            )}
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="flex flex-col gap-3 pt-2">
                                <Link
                                    to={ROUTES.SYSTEM.PROFILE}
                                    className="w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors"
                                >
                                    Ver minhas inscrições
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                                <Link
                                    to={ROUTES.SYSTEM.HOME}
                                    className="inline-flex items-center justify-center gap-2 text-gray-500 hover:text-gray-700 text-sm"
                                >
                                    <Home className="h-4 w-4" />
                                    Voltar para o início
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </Layout>
        );
    }

    return null;
}
