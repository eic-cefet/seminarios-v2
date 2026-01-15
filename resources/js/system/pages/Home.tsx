import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ArrowRight, BookOpen, Presentation, Wrench } from 'lucide-react';
import { Layout } from '../components/Layout';
import { SeminarCard } from '../components/SeminarCard';
import { PageTitle } from '@shared/components/PageTitle';
import { seminarsApi, subjectsApi, statsApi } from '@shared/api/client';

export default function Home() {
    const { data: seminarsData, isLoading: loadingSeminars } = useQuery({
        queryKey: ['upcomingSeminars'],
        queryFn: () => seminarsApi.upcoming(),
    });

    const { data: subjectsData, isLoading: loadingSubjects } = useQuery({
        queryKey: ['topSubjects'],
        queryFn: () => subjectsApi.list({ sort: 'seminars', limit: 8 }),
    });

    const { data: statsData } = useQuery({
        queryKey: ['stats'],
        queryFn: () => statsApi.get(),
    });

    const seminars = seminarsData?.data ?? [];
    const subjects = subjectsData?.data ?? [];
    const stats = statsData?.data;

    return (
        <>
            <PageTitle title="Início" />
            <Layout>
            {/* Hero Section */}
            <section className="bg-primary-600">
                <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                    <div className="text-center">
                        <h1 className="text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                            Seminários EIC
                        </h1>
                        <p className="mx-auto mt-4 max-w-2xl text-lg text-primary-100">
                            Escola de Informática e Computação do CEFET-RJ
                        </p>
                        <p className="mx-auto mt-2 max-w-2xl text-primary-200">
                            Participe de apresentações e workshops sobre temas relevantes da área
                            de computação
                        </p>
                        <div className="mt-8 flex justify-center gap-4">
                            <Link
                                to="/apresentacoes"
                                className="inline-flex items-center rounded-md bg-white px-6 py-3 text-sm font-semibold text-primary-600 shadow-sm hover:bg-primary-50 transition-colors"
                            >
                                Ver apresentações
                                <ArrowRight className="ml-2 h-4 w-4" />
                            </Link>
                            <Link
                                to="/workshops"
                                className="inline-flex items-center rounded-md border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white hover:bg-white/20 transition-colors"
                            >
                                Ver workshops
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            {/* Stats Section */}
            <section className="border-b border-gray-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div className="flex items-center gap-4 rounded-lg bg-gray-50 p-4">
                            <div className="rounded-full bg-primary-100 p-3">
                                <BookOpen className="h-6 w-6 text-primary-600" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900">
                                    {stats?.subjects ?? 0}
                                </p>
                                <p className="text-sm text-gray-500">Disciplinas</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-4 rounded-lg bg-gray-50 p-4">
                            <div className="rounded-full bg-primary-100 p-3">
                                <Presentation className="h-6 w-6 text-primary-600" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900">
                                    {stats?.seminars ?? 0}
                                </p>
                                <p className="text-sm text-gray-500">Seminários</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-4 rounded-lg bg-gray-50 p-4">
                            <div className="rounded-full bg-primary-100 p-3">
                                <Wrench className="h-6 w-6 text-primary-600" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900">
                                    {stats?.workshops ?? 0}
                                </p>
                                <p className="text-sm text-gray-500">Workshops</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Upcoming Seminars */}
            <section className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-2xl font-bold text-gray-900">Próximos Seminários</h2>
                            <p className="mt-1 text-gray-500">
                                Confira os seminários que acontecerão em breve
                            </p>
                        </div>
                        <Link
                            to="/apresentacoes"
                            className="hidden sm:inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-700"
                        >
                            Ver todos
                            <ArrowRight className="ml-1 h-4 w-4" />
                        </Link>
                    </div>

                    {loadingSeminars ? (
                        <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {[1, 2, 3].map((i) => (
                                <div
                                    key={i}
                                    className="h-48 animate-pulse rounded-lg bg-gray-200"
                                />
                            ))}
                        </div>
                    ) : seminars.length > 0 ? (
                        <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {seminars.map((seminar) => (
                                <SeminarCard key={seminar.id} seminar={seminar} />
                            ))}
                        </div>
                    ) : (
                        <div className="mt-6 rounded-lg border border-dashed border-gray-300 p-8 text-center">
                            <p className="text-gray-500">
                                Nenhum seminário agendado no momento
                            </p>
                        </div>
                    )}

                    <div className="mt-6 sm:hidden">
                        <Link
                            to="/apresentacoes"
                            className="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Ver todos os seminários
                            <ArrowRight className="ml-1 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </section>

            {/* Subjects Section */}
            <section className="border-t border-gray-200 bg-gray-50 py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-2xl font-bold text-gray-900">Disciplinas</h2>
                            <p className="mt-1 text-gray-500">
                                Explore os seminários por disciplina
                            </p>
                        </div>
                        <Link
                            to="/disciplinas"
                            className="hidden sm:inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-700"
                        >
                            Ver todas
                            <ArrowRight className="ml-1 h-4 w-4" />
                        </Link>
                    </div>

                    {loadingSubjects ? (
                        <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {[1, 2, 3, 4].map((i) => (
                                <div
                                    key={i}
                                    className="h-24 animate-pulse rounded-lg bg-gray-200"
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {subjects.map((subject) => (
                                <Link
                                    key={subject.id}
                                    to={`/disciplina/${subject.id}`}
                                    className="group rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all hover:border-primary-300 hover:shadow-md"
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs text-gray-500">
                                            {subject.seminarsCount ?? 0} seminários
                                        </span>
                                    </div>
                                    <h3 className="mt-2 font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                                        {subject.name}
                                    </h3>
                                </Link>
                            ))}
                        </div>
                    )}

                    <div className="mt-6 sm:hidden">
                        <Link
                            to="/disciplinas"
                            className="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Ver todas as disciplinas
                            <ArrowRight className="ml-1 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </section>
        </Layout>
        </>
    );
}
