import { useParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ArrowLeft, BookOpen } from 'lucide-react';
import { Layout } from '../components/Layout';
import { SeminarCard } from '../components/SeminarCard';
import { PageTitle } from '@shared/components/PageTitle';
import { subjectsApi, seminarsApi } from '@shared/api/client';

export default function SubjectSeminars() {
    const { id } = useParams<{ id: string }>();
    const subjectId = Number(id);

    const { data: subjectData, isLoading: loadingSubject } = useQuery({
        queryKey: ['subject', subjectId],
        queryFn: () => subjectsApi.get(subjectId),
        enabled: !isNaN(subjectId),
    });

    const { data: seminarsData, isLoading: loadingSeminars } = useQuery({
        queryKey: ['subjectSeminars', subjectId],
        queryFn: () => seminarsApi.bySubject(subjectId, { direction: 'desc' }),
        enabled: !isNaN(subjectId),
    });

    const subject = subjectData?.data;
    const seminars = seminarsData?.data ?? [];

    const isLoading = loadingSubject || loadingSeminars;

    if (isLoading) {
        return (
            <>
                <PageTitle title="Carregando..." />
                <Layout>
                    <div className="bg-white border-b border-gray-200">
                        <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                            <div className="h-8 w-48 animate-pulse rounded bg-gray-200" />
                            <div className="mt-2 h-4 w-72 animate-pulse rounded bg-gray-200" />
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
            </>
        );
    }

    if (!subject) {
        return (
            <>
                <PageTitle title="Disciplina não encontrada" />
                <Layout>
                    <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 text-center">
                        <BookOpen className="mx-auto h-12 w-12 text-gray-400" />
                        <h1 className="mt-4 text-2xl font-bold text-gray-900">
                            Disciplina não encontrada
                        </h1>
                        <p className="mt-2 text-gray-500">
                            A disciplina que você está procurando não existe ou foi removida.
                        </p>
                        <Link
                            to="/disciplinas"
                            className="mt-6 inline-flex items-center text-primary-600 hover:text-primary-700"
                        >
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Voltar para disciplinas
                        </Link>
                    </div>
                </Layout>
            </>
        );
    }

    return (
        <>
            <PageTitle title={subject.name} />
            <Layout>
                <div className="bg-white border-b border-gray-200">
                    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                        <Link
                            to="/disciplinas"
                            className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
                        >
                            <ArrowLeft className="mr-1 h-4 w-4" />
                            Todas as disciplinas
                        </Link>
                        <h1 className="text-3xl font-bold text-gray-900">{subject.name}</h1>
                    <p className="mt-2 text-gray-500">
                        {subject.seminarsCount ?? 0} seminários nesta disciplina
                    </p>
                </div>
            </div>

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {seminars.length > 0 ? (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {seminars.map((seminar) => (
                            <SeminarCard key={seminar.id} seminar={seminar} showSubject={false} />
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed border-gray-300 p-12 text-center">
                        <BookOpen className="mx-auto h-12 w-12 text-gray-400" />
                        <h3 className="mt-4 text-lg font-medium text-gray-900">
                            Nenhum seminário encontrado
                        </h3>
                        <p className="mt-2 text-gray-500">
                            Esta disciplina ainda não possui seminários cadastrados.
                        </p>
                    </div>
                )}
            </div>
            </Layout>
        </>
    );
}
