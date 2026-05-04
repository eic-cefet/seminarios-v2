import { Link } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { Wrench, Calendar } from "lucide-react";
import { Layout } from "../components/Layout";
import { PageTitle } from "@shared/components/PageTitle";
import { LoadingRegion } from "@shared/components/LoadingRegion";
import { Skeleton } from "@shared/components/Skeleton";
import { ROUTES } from "@shared/config/routes";
import { workshopsApi } from "@shared/api/client";

export default function Workshops() {
    const { data: workshopsData, isLoading } = useQuery({
        queryKey: ["workshops"],
        queryFn: () => workshopsApi.list(),
    });

    const workshops = workshopsData?.data ?? [];

    return (
        <>
            <PageTitle title="Workshops" />
            <Layout>
                <div className="bg-white border-b border-gray-200">
                    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                        <h1 className="text-3xl font-bold text-gray-900">
                            Workshops
                        </h1>
                        <p className="mt-2 text-gray-500">
                            Workshops práticos com múltiplas sessões
                        </p>
                    </div>
                </div>

                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    {isLoading ? (
                        <LoadingRegion
                            label="Carregando workshops"
                            className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            {[1, 2, 3].map((i) => (
                                <Skeleton key={i} className="h-48 rounded-lg" />
                            ))}
                        </LoadingRegion>
                    ) : workshops.length > 0 ? (
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {workshops.map((workshop) => (
                                <Link
                                    key={workshop.id}
                                    to={ROUTES.SYSTEM.WORKSHOP_DETAILS(workshop.slug)}
                                    className="group rounded-lg border border-gray-200 bg-white overflow-hidden shadow-sm transition-all hover:border-primary-300 hover:shadow-md"
                                >
                                    <div className="bg-primary-600 px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className="rounded-full bg-white/20 p-2">
                                                <Wrench className="h-5 w-5 text-white" />
                                            </div>
                                            <h2 className="font-semibold text-white group-hover:text-primary-100 transition-colors line-clamp-1">
                                                {workshop.name}
                                            </h2>
                                        </div>
                                    </div>
                                    <div className="p-6">
                                        {workshop.description && (
                                            <p className="text-sm text-gray-500 line-clamp-2">
                                                {workshop.description}
                                            </p>
                                        )}
                                        <div className="mt-4 flex items-center gap-2 text-sm text-gray-500">
                                            <Calendar className="h-4 w-4" />
                                            <span>
                                                {workshop.seminarsCount ?? 0}{" "}
                                                sessões
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-lg border border-dashed border-gray-300 p-12 text-center">
                            <Wrench className="mx-auto h-12 w-12 text-gray-400" />
                            <h3 className="mt-4 text-lg font-medium text-gray-900">
                                Nenhum workshop encontrado
                            </h3>
                            <p className="mt-2 text-gray-500">
                                Os workshops serão exibidos aqui quando
                                disponíveis.
                            </p>
                        </div>
                    )}
                </div>
            </Layout>
        </>
    );
}
