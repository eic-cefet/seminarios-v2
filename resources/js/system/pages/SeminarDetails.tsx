import { useParams, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import {
    ArrowLeft,
    Calendar,
    MapPin,
    Users,
    Star,
    ExternalLink,
    User,
    Linkedin,
    Github,
} from 'lucide-react';
import { Layout } from '../components/Layout';
import { Badge } from '../components/Badge';
import { seminarsApi } from '@shared/api/client';
import { formatDateTime, cn, containsHTML } from '@shared/lib/utils';
import DOMPurify from 'dompurify';

export default function SeminarDetails() {
    const { slug } = useParams<{ slug: string }>();

    const { data: seminarData, isLoading, error } = useQuery({
        queryKey: ['seminar', slug],
        queryFn: () => seminarsApi.get(slug!),
        enabled: !!slug,
    });

    const seminar = seminarData?.data;

    if (isLoading) {
        return (
            <Layout>
                <div className="bg-white border-b border-gray-200">
                    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                        <div className="h-4 w-32 animate-pulse rounded bg-gray-200 mb-4" />
                        <div className="h-10 w-96 animate-pulse rounded bg-gray-200" />
                        <div className="h-4 w-64 animate-pulse rounded bg-gray-200 mt-4" />
                    </div>
                </div>
                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <div className="h-64 animate-pulse rounded-lg bg-gray-200" />
                </div>
            </Layout>
        );
    }

    if (error || !seminar) {
        return (
            <Layout>
                <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 text-center">
                    <Calendar className="mx-auto h-12 w-12 text-gray-400" />
                    <h1 className="mt-4 text-2xl font-bold text-gray-900">
                        Seminário não encontrado
                    </h1>
                    <p className="mt-2 text-gray-500">
                        O seminário que você está procurando não existe ou foi removido.
                    </p>
                    <Link
                        to="/apresentacoes"
                        className="mt-6 inline-flex items-center text-primary-600 hover:text-primary-700"
                    >
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Voltar para apresentações
                    </Link>
                </div>
            </Layout>
        );
    }

    const isExpired = seminar.isExpired;

    return (
        <Layout>
            {/* Header */}
            <div className={cn('border-b border-gray-200', isExpired ? 'bg-gray-100' : 'bg-white')}>
                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <Link
                        to="/apresentacoes"
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
                    >
                        <ArrowLeft className="mr-1 h-4 w-4" />
                        Todas as apresentações
                    </Link>

                    <div className="flex flex-wrap items-center gap-2 mb-4">
                        {seminar.seminarType && (
                            <Badge variant={isExpired ? 'expired' : 'default'}>
                                {seminar.seminarType.name}
                            </Badge>
                        )}
                        {isExpired && <Badge variant="expired">Encerrado</Badge>}
                        {seminar.subjects?.map((subject) => (
                            <Badge key={subject.id} variant="default">
                                {subject.name}
                            </Badge>
                        ))}
                    </div>

                    <h1
                        className={cn(
                            'text-3xl font-bold',
                            isExpired ? 'text-gray-500' : 'text-gray-900'
                        )}
                    >
                        {seminar.name}
                    </h1>

                    <div className="mt-4 flex flex-wrap items-center gap-6 text-gray-500">
                        <span className="flex items-center gap-2">
                            <Calendar className="h-5 w-5" />
                            {formatDateTime(seminar.scheduledAt)}
                        </span>
                        {seminar.location && (
                            <span className="flex items-center gap-2">
                                <MapPin className="h-5 w-5" />
                                {seminar.location.name}
                            </span>
                        )}
                        {seminar.registrationsCount !== undefined && (
                            <span className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                {seminar.registrationsCount} inscritos
                            </span>
                        )}
                        {seminar.averageRating && (
                            <span className="flex items-center gap-2">
                                <Star className="h-5 w-5 fill-yellow-400 text-yellow-400" />
                                {seminar.averageRating.toFixed(1)}
                            </span>
                        )}
                    </div>
                </div>
            </div>

            {/* Content */}
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="grid gap-8 lg:grid-cols-3">
                    {/* Main Content */}
                    <div className="lg:col-span-2 space-y-8">
                        {/* Description */}
                        {seminar.description && (
                            <section>
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Sobre o seminário
                                </h2>
                                {containsHTML(seminar.description) ? (
                                    <div
                                        className="html-content"
                                        dangerouslySetInnerHTML={{
                                            __html: DOMPurify.sanitize(seminar.description),
                                        }}
                                    />
                                ) : (
                                    <p className="text-gray-600 whitespace-pre-line">
                                        {seminar.description}
                                    </p>
                                )}
                            </section>
                        )}

                        {/* Speakers */}
                        {seminar.speakers && seminar.speakers.length > 0 && (
                            <section>
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Palestrantes
                                </h2>
                                <div className="space-y-4">
                                    {seminar.speakers.map((speaker) => (
                                        <div
                                            key={speaker.id}
                                            className="flex items-start gap-4 rounded-lg border border-gray-200 bg-white p-4"
                                        >
                                            <div className="flex-shrink-0 rounded-full bg-primary-100 p-3">
                                                <User className="h-6 w-6 text-primary-600" />
                                            </div>
                                            <div className="flex-1">
                                                <h3 className="font-semibold text-gray-900">
                                                    {speaker.name}
                                                </h3>
                                                {speaker.speakerData?.position && (
                                                    <p className="text-sm text-gray-500">
                                                        {speaker.speakerData.position}
                                                        {speaker.speakerData.company &&
                                                            ` @ ${speaker.speakerData.company}`}
                                                    </p>
                                                )}
                                                {speaker.speakerData?.bio && (
                                                    <p className="mt-2 text-sm text-gray-600">
                                                        {speaker.speakerData.bio}
                                                    </p>
                                                )}
                                                <div className="mt-3 flex gap-3">
                                                    {speaker.speakerData?.linkedin && (
                                                        <a
                                                            href={speaker.speakerData.linkedin}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="text-gray-400 hover:text-primary-600"
                                                        >
                                                            <Linkedin className="h-5 w-5" />
                                                        </a>
                                                    )}
                                                    {speaker.speakerData?.github && (
                                                        <a
                                                            href={speaker.speakerData.github}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="text-gray-400 hover:text-primary-600"
                                                        >
                                                            <Github className="h-5 w-5" />
                                                        </a>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Action Card */}
                        <div className="rounded-lg border border-gray-200 bg-white p-6">
                            {!isExpired ? (
                                <>
                                    <h3 className="font-semibold text-gray-900 mb-4">
                                        Inscreva-se neste seminário
                                    </h3>
                                    <button className="w-full rounded-md bg-primary-600 px-4 py-3 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors">
                                        Realizar inscrição
                                    </button>
                                    <p className="mt-3 text-xs text-gray-500 text-center">
                                        Você receberá um lembrete por e-mail antes do evento
                                    </p>
                                </>
                            ) : (
                                <>
                                    <h3 className="font-semibold text-gray-500 mb-2">
                                        Este seminário já foi realizado
                                    </h3>
                                    <p className="text-sm text-gray-400">
                                        Confira outros seminários disponíveis
                                    </p>
                                </>
                            )}
                        </div>

                        {/* External Link */}
                        {seminar.link && (
                            <a
                                href={seminar.link}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white p-4 text-sm font-medium text-primary-600 hover:bg-gray-50 transition-colors"
                            >
                                <ExternalLink className="h-4 w-4" />
                                Link do evento
                            </a>
                        )}

                        {/* Workshop Info */}
                        {seminar.workshop && (
                            <div className="rounded-lg border border-gray-200 bg-white p-6">
                                <p className="text-xs text-gray-500 uppercase tracking-wide mb-2">
                                    Parte do workshop
                                </p>
                                <Link
                                    to={`/workshop/${seminar.workshop.id}`}
                                    className="font-semibold text-primary-600 hover:text-primary-700"
                                >
                                    {seminar.workshop.name}
                                </Link>
                            </div>
                        )}

                        {/* Location Details */}
                        {seminar.location && seminar.location.address && (
                            <div className="rounded-lg border border-gray-200 bg-white p-6">
                                <h3 className="font-semibold text-gray-900 mb-2">Local</h3>
                                <p className="text-sm text-gray-600">{seminar.location.name}</p>
                                <p className="text-sm text-gray-500">{seminar.location.address}</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </Layout>
    );
}
