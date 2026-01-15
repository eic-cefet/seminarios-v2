import { useParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ArrowLeft, Wrench, Calendar } from 'lucide-react';
import { Layout } from '../components/Layout';
import { SeminarCard } from '../components/SeminarCard';
import { workshopsApi } from '@shared/api/client';

export default function WorkshopDetails() {
    const { id } = useParams<{ id: string }>();
    const workshopId = Number(id);

    const { data: workshopData, isLoading: loadingWorkshop } = useQuery({
        queryKey: ['workshop', workshopId],
        queryFn: () => workshopsApi.get(workshopId),
        enabled: !isNaN(workshopId),
    });

    const { data: seminarsData, isLoading: loadingSeminars } = useQuery({
        queryKey: ['workshopSeminars', workshopId],
        queryFn: () => workshopsApi.seminars(workshopId),
        enabled: !isNaN(workshopId),
    });

    const workshop = workshopData?.data;
    const seminars = seminarsData?.data ?? [];

    const isLoading = loadingWorkshop || loadingSeminars;

    if (isLoading) {
        return (
            <Layout>
                <div className="bg-primary-600">
                    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                        <div className="h-8 w-48 animate-pulse rounded bg-white/30" />
                        <div className="mt-2 h-4 w-72 animate-pulse rounded bg-white/20" />
                    </div>
                </div>
                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {[1, 2, 3].map((i) => (
                            <div key={i} className="h-48 animate-pulse rounded-lg bg-gray-200" />
                        ))}
                    </div>
                </div>
            </Layout>
        );
    }

    if (!workshop) {
        return (
            <Layout>
                <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 text-center">
                    <Wrench className="mx-auto h-12 w-12 text-gray-400" />
                    <h1 className="mt-4 text-2xl font-bold text-gray-900">
                        Workshop não encontrado
                    </h1>
                    <p className="mt-2 text-gray-500">
                        O workshop que você está procurando não existe ou foi removido.
                    </p>
                    <Link
                        to="/workshops"
                        className="mt-6 inline-flex items-center text-primary-600 hover:text-primary-700"
                    >
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Voltar para workshops
                    </Link>
                </div>
            </Layout>
        );
    }

    return (
        <Layout>
            {/* Header */}
            <div className="bg-primary-600">
                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <Link
                        to="/workshops"
                        className="inline-flex items-center text-sm text-primary-100 hover:text-white mb-4"
                    >
                        <ArrowLeft className="mr-1 h-4 w-4" />
                        Todos os workshops
                    </Link>
                    <div className="flex items-center gap-4">
                        <div className="rounded-full bg-white/20 p-3">
                            <Wrench className="h-8 w-8 text-white" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold text-white">{workshop.name}</h1>
                            <p className="mt-1 text-primary-100 flex items-center gap-2">
                                <Calendar className="h-4 w-4" />
                                {seminars.length} sessões
                            </p>
                        </div>
                    </div>
                    {workshop.description && (
                        <p className="mt-4 max-w-2xl text-primary-100">{workshop.description}</p>
                    )}
                </div>
            </div>

            {/* Sessions */}
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <h2 className="text-xl font-semibold text-gray-900 mb-6">Sessões do Workshop</h2>

                {seminars.length > 0 ? (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {seminars.map((seminar) => (
                            <SeminarCard key={seminar.id} seminar={seminar} />
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed border-gray-300 p-12 text-center">
                        <Calendar className="mx-auto h-12 w-12 text-gray-400" />
                        <h3 className="mt-4 text-lg font-medium text-gray-900">
                            Nenhuma sessão encontrada
                        </h3>
                        <p className="mt-2 text-gray-500">
                            Este workshop ainda não possui sessões cadastradas.
                        </p>
                    </div>
                )}
            </div>
        </Layout>
    );
}
