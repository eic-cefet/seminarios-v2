import { profileApi } from "@shared/api/client";
import { Pagination } from "@shared/components/Pagination";
import { formatDateTime } from "@shared/lib/utils";
import { analytics } from "@shared/lib/analytics";
import { useQuery } from "@tanstack/react-query";
import { Download, FileText, Loader2 } from "lucide-react";
import { useState } from "react";
import { Link } from "react-router-dom";
import { Badge } from "../Badge";

export function CertificatesSection() {
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ["profile", "certificates", page],
        queryFn: () => profileApi.certificates({ page, per_page: 10 }),
    });

    const certificates = data?.data ?? [];
    const meta = data?.meta;

    return (
        <section className="rounded-lg border border-gray-200 bg-white">
            <div className="border-b border-gray-200 px-6 py-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <FileText className="h-5 w-5 text-gray-400" />
                        <h2 className="text-lg font-semibold text-gray-900">
                            Meus certificados
                        </h2>
                    </div>
                    {meta && meta.total > 0 && (
                        <span className="text-sm text-gray-500">
                            {meta.total} certificado
                            {meta.total !== 1 ? "s" : ""}
                        </span>
                    )}
                </div>
            </div>

            <div className="divide-y divide-gray-200">
                {isLoading ? (
                    <div className="flex items-center justify-center py-8">
                        <Loader2 className="h-6 w-6 animate-spin text-gray-400" />
                    </div>
                ) : certificates.length === 0 ? (
                    <div className="px-6 py-8 text-center">
                        <FileText className="mx-auto h-8 w-8 text-gray-300" />
                        <p className="mt-2 text-sm text-gray-500">
                            Você ainda não possui certificados.
                        </p>
                        <p className="mt-1 text-xs text-gray-400">
                            Certificados são gerados após a confirmação de
                            presença em um seminário.
                        </p>
                    </div>
                ) : (
                    <>
                        {certificates.map((certificate) => (
                            <div
                                key={certificate.id}
                                className="flex items-center justify-between px-6 py-4"
                            >
                                <div className="flex-1 min-w-0">
                                    <Link
                                        to={`/seminario/${certificate.seminar.slug}`}
                                        className="font-medium text-gray-900 hover:text-primary-600 cursor-pointer"
                                    >
                                        {certificate.seminar.name}
                                    </Link>
                                    <div className="mt-1 flex items-center gap-2 text-sm text-gray-500">
                                        {certificate.seminar.seminar_type && (
                                            <Badge variant="default">
                                                {
                                                    certificate.seminar
                                                        .seminar_type.name
                                                }
                                            </Badge>
                                        )}
                                        {certificate.seminar.scheduled_at && (
                                            <span>
                                                {formatDateTime(
                                                    certificate.seminar
                                                        .scheduled_at,
                                                )}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <a
                                        href={`/certificado/${certificate.certificate_code}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        onClick={() =>
                                            analytics.event("certificate_download", {
                                                seminar_id: certificate.seminar.id,
                                            })
                                        }
                                        className="inline-flex items-center gap-1.5 rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors cursor-pointer"
                                    >
                                        <Download className="h-3.5 w-3.5" />
                                        Baixar
                                    </a>
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
