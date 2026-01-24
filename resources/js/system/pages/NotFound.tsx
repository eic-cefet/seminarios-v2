import { Link } from "react-router-dom";
import { Home } from "lucide-react";
import { Layout } from "../components/Layout";
import { PageTitle } from "@shared/components/PageTitle";

export default function NotFound() {
    return (
        <>
            <PageTitle title="Página não encontrada" />
            <Layout>
                <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                    <div className="text-center">
                        <p className="text-sm font-semibold text-primary-600">
                            404
                        </p>
                        <h1 className="mt-2 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                            Página não encontrada
                        </h1>
                        <p className="mt-4 text-lg text-gray-500">
                            A página que você está procurando não existe ou foi
                            movida.
                        </p>
                        <div className="mt-8">
                            <Link
                                to="/"
                                className="inline-flex items-center rounded-md bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors"
                            >
                                <Home className="mr-2 h-4 w-4" />
                                Voltar para o início
                            </Link>
                        </div>
                    </div>
                </div>
            </Layout>
        </>
    );
}
