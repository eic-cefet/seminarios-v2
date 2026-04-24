import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { dataPrivacyApi } from "@shared/api/client";
import { PageTitle } from "@shared/components/PageTitle";
import { ROUTES } from "@shared/config/routes";
import { getErrorMessage } from "@shared/lib/errors";
import { Layout } from "../components/Layout";

type State =
    | { status: "loading" }
    | { status: "success"; scheduledFor: string }
    | { status: "error"; message: string };

export default function DeletionConfirm() {
    const { token } = useParams<{ token: string }>();
    const [state, setState] = useState<State>({ status: "loading" });

    useEffect(() => {
        if (!token) {
            setState({ status: "error", message: "Link inválido." });
            return;
        }
        dataPrivacyApi
            .confirmDeletion(token)
            .then((r) =>
                setState({
                    status: "success",
                    scheduledFor: r.scheduled_for,
                }),
            )
            .catch((err) =>
                setState({ status: "error", message: getErrorMessage(err) }),
            );
    }, [token]);

    return (
        <>
            <PageTitle title="Confirmar exclusão de conta" />
            <Layout>
                <div className="mx-auto max-w-xl px-4 py-12 sm:px-6 lg:px-8">
                    {state.status === "loading" && (
                        <p className="text-sm text-gray-600">
                            Confirmando sua solicitação…
                        </p>
                    )}
                    {state.status === "success" && (
                        <div className="rounded-lg border border-green-200 bg-green-50 p-6">
                            <h1 className="text-lg font-semibold text-green-900">
                                Exclusão agendada
                            </h1>
                            <p className="mt-2 text-sm text-green-800">
                                Sua conta será excluída em{" "}
                                {state.scheduledFor.slice(0, 10)}. Você pode
                                cancelar a qualquer momento fazendo login
                                novamente ou pela tela de perfil.
                            </p>
                            <Link
                                to={ROUTES.SYSTEM.PROFILE}
                                className="mt-4 inline-block text-sm text-primary-700 underline"
                            >
                                Voltar ao perfil
                            </Link>
                        </div>
                    )}
                    {state.status === "error" && (
                        <div className="rounded-lg border border-red-200 bg-red-50 p-6">
                            <h1 className="text-lg font-semibold text-red-900">
                                Não foi possível confirmar
                            </h1>
                            <p className="mt-2 text-sm text-red-800">
                                {state.message}
                            </p>
                            <Link
                                to={ROUTES.SYSTEM.PROFILE}
                                className="mt-4 inline-block text-sm text-primary-700 underline"
                            >
                                Voltar ao perfil
                            </Link>
                        </div>
                    )}
                </div>
            </Layout>
        </>
    );
}
