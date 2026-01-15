import { useQuery } from '@tanstack/react-query';
import { Link, Navigate } from 'react-router-dom';
import {
    FileText,
    Download,
    Calendar,
    Loader2,
    ArrowLeft,
} from 'lucide-react';
import { Layout } from '../components/Layout';
import { Badge } from '../components/Badge';
import { PageTitle } from '@shared/components/PageTitle';
import { useAuth } from '@shared/contexts/AuthContext';
import { profileApi } from '@shared/api/client';
import { formatDateTime } from '@shared/lib/utils';

export default function Certificates() {
    const { user, isLoading: authLoading } = useAuth();

    const { data, isLoading } = useQuery({
        queryKey: ['profile', 'certificates'],
        queryFn: () => profileApi.certificates(),
        enabled: !!user,
    });

    // Redirect if not authenticated
    if (!authLoading && !user) {
        return <Navigate to="/login" replace />;
    }

    if (authLoading) {
        return (
            <Layout>
                <div className="flex min-h-[calc(100vh-4rem-4rem)] items-center justify-center">
                    <Loader2 className="h-8 w-8 animate-spin text-primary-600" />
                </div>
            </Layout>
        );
    }

    const certificates = data?.data ?? [];

    return (
        <>
            <PageTitle title="Certificados" />
            <Layout>
            <div className="bg-white border-b border-gray-200">
                <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                    <Link
                        to="/perfil"
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4 cursor-pointer"
                    >
                        <ArrowLeft className="mr-1 h-4 w-4" />
                        Voltar ao perfil
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900">Meus Certificados</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Certificados emitidos para os seminários que você participou
                    </p>
                </div>
            </div>

            <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="rounded-lg border border-gray-200 bg-white">
                    {isLoading ? (
                        <div className="flex items-center justify-center py-12">
                            <Loader2 className="h-8 w-8 animate-spin text-gray-400" />
                        </div>
                    ) : certificates.length === 0 ? (
                        <div className="px-6 py-12 text-center">
                            <FileText className="mx-auto h-12 w-12 text-gray-300" />
                            <h3 className="mt-4 text-lg font-medium text-gray-900">
                                Nenhum certificado disponível
                            </h3>
                            <p className="mt-2 text-sm text-gray-500">
                                Certificados são gerados após a confirmação de presença em um
                                seminário.
                            </p>
                            <Link
                                to="/apresentacoes"
                                className="mt-6 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer"
                            >
                                Ver apresentações
                            </Link>
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-200">
                            {certificates.map((certificate) => (
                                <div
                                    key={certificate.id}
                                    className="flex flex-col sm:flex-row sm:items-center justify-between px-6 py-4 gap-4"
                                >
                                    <div className="flex-1 min-w-0">
                                        <Link
                                            to={`/seminario/${certificate.seminar.slug}`}
                                            className="font-medium text-gray-900 hover:text-primary-600 cursor-pointer"
                                        >
                                            {certificate.seminar.name}
                                        </Link>
                                        <div className="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                            {certificate.seminar.seminar_type && (
                                                <Badge variant="default">
                                                    {certificate.seminar.seminar_type.name}
                                                </Badge>
                                            )}
                                            {certificate.seminar.scheduled_at && (
                                                <span className="flex items-center gap-1">
                                                    <Calendar className="h-3.5 w-3.5" />
                                                    {formatDateTime(certificate.seminar.scheduled_at)}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    <a
                                        href={`/certificado/${certificate.certificate_code}`}
                                        download
                                        className="inline-flex items-center gap-1.5 rounded-md bg-primary-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer"
                                    >
                                        <Download className="h-4 w-4" />
                                        Baixar PDF
                                    </a>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </Layout>
        </>
    );
}
