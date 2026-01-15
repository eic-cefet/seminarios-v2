import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { BookOpen } from 'lucide-react';
import { Layout } from '../components/Layout';
import { PageTitle } from '@shared/components/PageTitle';
import { subjectsApi } from '@shared/api/client';

export default function Subjects() {
    const { data: subjectsData, isLoading } = useQuery({
        queryKey: ['subjects'],
        queryFn: () => subjectsApi.list(),
    });

    const subjects = subjectsData?.data ?? [];

    return (
        <>
            <PageTitle title="Tópicos" />
            <Layout>
            <div className="bg-white border-b border-gray-200">
                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <h1 className="text-3xl font-bold text-gray-900">Disciplinas</h1>
                    <p className="mt-2 text-gray-500">
                        Explore os seminários organizados por disciplina
                    </p>
                </div>
            </div>

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {isLoading ? (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {[1, 2, 3, 4, 5, 6].map((i) => (
                            <div key={i} className="h-32 animate-pulse rounded-lg bg-gray-200" />
                        ))}
                    </div>
                ) : subjects.length > 0 ? (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {subjects.map((subject) => (
                            <Link
                                key={subject.id}
                                to={`/disciplina/${subject.id}`}
                                className="group rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-all hover:border-primary-300 hover:shadow-md"
                            >
                                <div className="flex items-start gap-4">
                                    <div className="rounded-full bg-primary-100 p-3">
                                        <BookOpen className="h-6 w-6 text-primary-600" />
                                    </div>
                                    <div className="flex-1">
                                        <h2 className="font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                                            {subject.name}
                                        </h2>
                                        <p className="mt-1 text-sm text-gray-500">
                                            {subject.seminarsCount ?? 0} seminários
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed border-gray-300 p-12 text-center">
                        <BookOpen className="mx-auto h-12 w-12 text-gray-400" />
                        <h3 className="mt-4 text-lg font-medium text-gray-900">
                            Nenhuma disciplina encontrada
                        </h3>
                        <p className="mt-2 text-gray-500">
                            As disciplinas serão exibidas aqui quando disponíveis.
                        </p>
                    </div>
                )}
            </div>
        </Layout>
        </>
    );
}
