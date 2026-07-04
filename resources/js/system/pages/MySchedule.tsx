import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { Link } from "react-router-dom";
import { ArrowLeft, Calendar, CalendarDays, Loader2, MapPin } from "lucide-react";
import { Layout } from "../components/Layout";
import { ProtectedRoute } from "../components/ProtectedRoute";
import { Badge } from "../components/Badge";
import { CalendarMenu } from "../components/CalendarMenu";
import { PageTitle } from "@shared/components/PageTitle";
import { Pagination } from "@shared/components/Pagination";
import { profileApi } from "@shared/api/client";
import { ROUTES } from "@shared/config/routes";
import { formatDateTime } from "@shared/lib/utils";
import { Button } from "@shared/components/Button";

export default function MySchedule() {
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ["profile", "schedule", page],
        queryFn: () => profileApi.schedule({ page, per_page: 10 }),
    });

    const registrations = data?.data ?? [];
    const meta = data?.meta;

    return (
        <ProtectedRoute>
            <PageTitle title="Minha agenda" />
            <Layout>
                <div className="bg-white border-b border-gray-200">
                    <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                        <Link
                            to={ROUTES.SYSTEM.PROFILE}
                            className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4 cursor-pointer"
                        >
                            <ArrowLeft className="mr-1 h-4 w-4" />
                            Voltar ao perfil
                        </Link>
                        <div className="flex items-center justify-between">
                            <h1 className="text-2xl font-bold text-gray-900">
                                Minha Agenda
                            </h1>
                            {meta && meta.total > 0 && (
                                <span className="text-sm text-gray-500">
                                    {meta.total} inscriç
                                    {meta.total !== 1 ? "ões" : "ão"}
                                </span>
                            )}
                        </div>
                        <p className="mt-1 text-sm text-gray-500">
                            Suas próximas apresentações, em ordem de data
                        </p>
                    </div>
                </div>

                <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8 space-y-6">
                    <div className="rounded-lg border border-gray-200 bg-white">
                        {isLoading ? (
                            <div className="flex items-center justify-center py-12">
                                <Loader2 className="h-8 w-8 animate-spin text-gray-400" />
                            </div>
                        ) : registrations.length === 0 ? (
                            <div className="px-6 py-12 text-center">
                                <CalendarDays className="mx-auto h-12 w-12 text-gray-300" />
                                <h3 className="mt-4 text-lg font-medium text-gray-900">
                                    Você não está inscrito em nenhum seminário
                                    futuro
                                </h3>
                                <p className="mt-2 text-sm text-gray-500">
                                    Inscreva-se em uma apresentação para vê-la
                                    aqui.
                                </p>
                                <Button asChild variant="primary" className="mt-6 px-4 py-2">
                                    <Link to={ROUTES.SYSTEM.PRESENTATIONS}>
                                        Ver apresentações
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <>
                                <div className="divide-y divide-gray-200">
                                    {registrations.map((registration) => (
                                        <div
                                            key={registration.id}
                                            className="flex flex-col sm:flex-row sm:items-center justify-between px-6 py-4 gap-4"
                                        >
                                            <div className="flex-1 min-w-0">
                                                <Link
                                                    to={ROUTES.SYSTEM.SEMINAR_DETAILS(registration.seminar.slug)}
                                                    className="font-medium text-gray-900 hover:text-primary-600 cursor-pointer"
                                                >
                                                    {registration.seminar.name}
                                                </Link>
                                                <div className="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                                    {registration.seminar
                                                        .seminar_type && (
                                                        <Badge variant="default">
                                                            {
                                                                registration
                                                                    .seminar
                                                                    .seminar_type
                                                                    .name
                                                            }
                                                        </Badge>
                                                    )}
                                                    {registration.seminar
                                                        .scheduled_at && (
                                                        <span className="flex items-center gap-1">
                                                            <Calendar className="h-3.5 w-3.5" />
                                                            {formatDateTime(
                                                                registration
                                                                    .seminar
                                                                    .scheduled_at,
                                                            )}
                                                        </span>
                                                    )}
                                                    {registration.seminar
                                                        .location && (
                                                        <span className="flex items-center gap-1">
                                                            <MapPin className="h-3.5 w-3.5" />
                                                            {
                                                                registration
                                                                    .seminar
                                                                    .location
                                                                    .name
                                                            }
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                            {registration.seminar
                                                .scheduled_at && (
                                                <CalendarMenu
                                                    contentAlign="end"
                                                    className="border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
                                                    event={{
                                                        title: registration.seminar.name,
                                                        startsAt: registration.seminar.scheduled_at,
                                                        endsAt: registration.seminar.ends_at ?? undefined,
                                                        location: registration.seminar.location?.name ?? null,
                                                        eventPath: ROUTES.SYSTEM.SEMINAR_DETAILS(registration.seminar.slug),
                                                        downloadPath: ROUTES.SYSTEM.SEMINAR_CALENDAR_ICS(registration.seminar.slug),
                                                    }}
                                                />
                                            )}
                                        </div>
                                    ))}
                                </div>
                                <Pagination
                                    currentPage={meta?.current_page ?? page}
                                    lastPage={meta?.last_page ?? 1}
                                    onPageChange={setPage}
                                />
                            </>
                        )}
                    </div>
                </div>
            </Layout>
        </ProtectedRoute>
    );
}
