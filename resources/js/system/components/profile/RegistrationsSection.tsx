import { profileApi } from "@shared/api/client";
import { Pagination } from "@shared/components/Pagination";
import { formatDateTime } from "@shared/lib/utils";
import { useQuery } from "@tanstack/react-query";
import { Calendar, Check, ChevronRight, Loader2, MapPin, X } from "lucide-react";
import { useState } from "react";
import { Link } from "react-router-dom";
import { Badge } from "../Badge";

export function RegistrationsSection() {
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ["profile", "registrations", page],
        queryFn: () => profileApi.registrations({ page, per_page: 10 }),
    });

    const registrations = data?.data ?? [];
    const meta = data?.meta;

    return (
        <section className="rounded-lg border border-gray-200 bg-white">
            <div className="border-b border-gray-200 px-6 py-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Calendar className="h-5 w-5 text-gray-400" />
                        <h2 className="text-lg font-semibold text-gray-900">
                            Minhas inscrições
                        </h2>
                    </div>
                    {meta && meta.total > 0 && (
                        <span className="text-sm text-gray-500">
                            {meta.total} inscriç{meta.total > 1 ? "ões" : "ão"}
                        </span>
                    )}
                </div>
            </div>

            <div className="divide-y divide-gray-200">
                {isLoading ? (
                    <div className="flex items-center justify-center py-8">
                        <Loader2 className="h-6 w-6 animate-spin text-gray-400" />
                    </div>
                ) : registrations.length === 0 ? (
                    <div className="px-6 py-8 text-center">
                        <Calendar className="mx-auto h-8 w-8 text-gray-300" />
                        <p className="mt-2 text-sm text-gray-500">
                            Você ainda não se inscreveu em nenhum seminário.
                        </p>
                        <Link
                            to="/apresentacoes"
                            className="mt-4 inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-700 cursor-pointer"
                        >
                            Ver apresentações
                            <ChevronRight className="ml-1 h-4 w-4" />
                        </Link>
                    </div>
                ) : (
                    <>
                        {registrations.map((registration) => (
                            <div
                                key={registration.id}
                                className="flex items-center justify-between px-6 py-4"
                            >
                                <div className="flex-1 min-w-0">
                                    <Link
                                        to={`/seminario/${registration.seminar.slug}`}
                                        className="font-medium text-gray-900 hover:text-primary-600 cursor-pointer"
                                    >
                                        {registration.seminar.name}
                                    </Link>
                                    <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                                        {registration.seminar.scheduled_at && (
                                            <span className="flex items-center gap-1">
                                                <Calendar className="h-3.5 w-3.5" />
                                                {formatDateTime(
                                                    registration.seminar
                                                        .scheduled_at,
                                                )}
                                            </span>
                                        )}
                                        {registration.seminar.location && (
                                            <span className="flex items-center gap-1">
                                                <MapPin className="h-3.5 w-3.5" />
                                                {
                                                    registration.seminar
                                                        .location.name
                                                }
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="ml-4 flex items-center gap-2">
                                    {registration.seminar.is_expired &&
                                        !registration.present && (
                                            <Badge variant="expired">
                                                <X className="mr-1 h-3 w-3" />
                                                Ausente
                                            </Badge>
                                        )}

                                    {registration.present && (
                                        <Badge variant="default">
                                            <Check className="mr-1 h-3 w-3" />
                                            Presente
                                        </Badge>
                                    )}

                                    {!registration.seminar.is_expired &&
                                        !registration.present && (
                                            <Badge variant="default">
                                                Inscrito
                                            </Badge>
                                        )}
                                </div>
                            </div>
                        ))}
                        <Pagination
                            currentPage={meta?.current_page ?? page}
                            lastPage={meta?.last_page ?? 1}
                            onPageChange={setPage}
                        />
                    </>
                )}
            </div>
        </section>
    );
}
