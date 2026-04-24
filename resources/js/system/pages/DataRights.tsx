import type { ReactNode } from "react";
import { Link } from "react-router-dom";
import { PageTitle } from "@shared/components/PageTitle";
import { ROUTES } from "@shared/config/routes";
import { Layout } from "../components/Layout";

const DPO_EMAIL = "lgpd@eic-seminarios.com";

export default function DataRights() {
    return (
        <>
            <PageTitle title="Central de Direitos LGPD" />
            <Layout>
                <header className="border-b border-gray-200 bg-gradient-to-b from-primary-50 to-white">
                    <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
                        <p className="text-xs font-semibold uppercase tracking-wider text-primary-700">
                            LGPD · Art. 18
                        </p>
                        <h1 className="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                            Central de Direitos LGPD
                        </h1>
                        <p className="mt-4 text-base leading-relaxed text-gray-600">
                            A LGPD (Lei 13.709/2018) garante aos titulares dos
                            dados uma série de direitos. Abaixo você encontra
                            como exercer cada um deles no Sistema de Seminários
                            da EIC.
                        </p>
                    </div>
                </header>

                <article className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
                    <div className="space-y-10">
                        <Section
                            icon={<IconDownload />}
                            title="Exportar seus dados"
                        >
                            <p className="text-base leading-relaxed text-gray-700">
                                Você pode baixar um arquivo{" "}
                                <code className="rounded bg-gray-100 px-1 py-0.5 text-[0.85em]">
                                    .zip
                                </code>{" "}
                                contendo todos os dados que mantemos sobre
                                você, em formato JSON estruturado.
                            </p>
                            <PathChip>
                                Perfil → Privacidade → Exportar meus dados
                            </PathChip>
                            <p className="mt-3 text-sm text-gray-600">
                                O arquivo é enviado por e-mail e fica
                                disponível por 1 dia.
                            </p>
                        </Section>

                        <Section
                            icon={<IconEdit />}
                            title="Corrigir dados incompletos ou desatualizados"
                        >
                            <p className="text-base leading-relaxed text-gray-700">
                                Edite suas informações na tela de{" "}
                                <InlineLink to={ROUTES.SYSTEM.PROFILE}>
                                    Perfil
                                </InlineLink>
                                .
                            </p>
                        </Section>

                        <Section
                            icon={<IconTrash />}
                            title="Excluir sua conta"
                            tone="danger"
                        >
                            <p className="text-base leading-relaxed text-gray-700">
                                Você pode solicitar a exclusão da sua conta.
                            </p>
                            <PathChip tone="danger">
                                Perfil → Privacidade → Excluir minha conta
                            </PathChip>
                            <p className="mt-3 text-sm text-gray-600">
                                O processo inclui um período de carência de 30
                                dias durante o qual você pode cancelar a
                                solicitação apenas fazendo login novamente.
                                Após o prazo, seus dados são pseudonimizados:
                                nome e e-mail são substituídos por valores
                                genéricos, mas registros acadêmicos de presença
                                são preservados como documentação institucional
                                (legítimo interesse, Art. 16, II, LGPD).
                            </p>
                        </Section>

                        <Section
                            icon={<IconShield />}
                            title="Gerenciar consentimentos"
                        >
                            <p className="text-base leading-relaxed text-gray-700">
                                Revogue ou conceda consentimentos (cookies,
                                análise de IA em comentários) na{" "}
                                <InlineLink
                                    to={ROUTES.SYSTEM.COOKIE_PREFERENCES}
                                >
                                    Central de Preferências
                                </InlineLink>{" "}
                                ou diretamente nos respectivos formulários.
                            </p>
                        </Section>

                        <Section
                            icon={<IconExchange />}
                            title="Portabilidade para outro serviço"
                        >
                            <p className="text-base leading-relaxed text-gray-700">
                                O arquivo JSON exportado acima já atende ao
                                direito de portabilidade previsto no Art. 18, V
                                da LGPD.
                            </p>
                        </Section>

                        <Section
                            icon={<IconMail />}
                            title="Fazer uma solicitação formal"
                        >
                            <p className="text-base leading-relaxed text-gray-700">
                                Para qualquer solicitação que não possa ser
                                cumprida pelas opções acima, entre em contato
                                com nosso Encarregado pelo Tratamento de Dados
                                Pessoais:
                            </p>
                            <a
                                href={`mailto:${DPO_EMAIL}`}
                                className="mt-4 inline-flex items-center gap-2 rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm font-medium text-primary-800 hover:bg-primary-100"
                            >
                                <svg
                                    aria-hidden="true"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    className="h-4 w-4"
                                >
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                                {DPO_EMAIL}
                            </a>
                            <p className="mt-4 text-sm text-gray-600">
                                Responderemos em prazo razoável, nos termos da
                                LGPD. Se considerar a resposta insatisfatória,
                                você pode também apresentar reclamação à{" "}
                                <a
                                    href="https://www.gov.br/anpd/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="font-medium text-primary-700 underline-offset-2 hover:underline"
                                >
                                    Autoridade Nacional de Proteção de Dados
                                    (ANPD)
                                </a>
                                .
                            </p>
                        </Section>
                    </div>
                </article>
            </Layout>
        </>
    );
}

function Section({
    icon,
    title,
    children,
    tone = "default",
}: {
    icon: ReactNode;
    title: string;
    children: ReactNode;
    tone?: "default" | "danger";
}) {
    const chipClass =
        tone === "danger"
            ? "bg-red-100 text-red-700"
            : "bg-primary-100 text-primary-700";
    return (
        <section className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div className="flex items-start gap-4">
                <span
                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${chipClass}`}
                >
                    {icon}
                </span>
                <div className="flex-1">
                    <h2 className="text-lg font-semibold tracking-tight text-gray-900">
                        {title}
                    </h2>
                    <div className="mt-3">{children}</div>
                </div>
            </div>
        </section>
    );
}

function PathChip({
    children,
    tone = "default",
}: {
    children: ReactNode;
    tone?: "default" | "danger";
}) {
    const cls =
        tone === "danger"
            ? "border-red-200 bg-red-50 text-red-900"
            : "border-gray-200 bg-gray-50 text-gray-800";
    return (
        <p
            className={`mt-3 flex max-w-full flex-wrap items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium break-words ${cls}`}
        >
            <svg
                aria-hidden="true"
                viewBox="0 0 20 20"
                fill="currentColor"
                className="h-4 w-4 shrink-0 opacity-60"
            >
                <path
                    fillRule="evenodd"
                    d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                    clipRule="evenodd"
                />
            </svg>
            <span className="min-w-0 break-words">{children}</span>
        </p>
    );
}

function InlineLink({ to, children }: { to: string; children: ReactNode }) {
    return (
        <Link
            to={to}
            className="font-medium text-primary-700 underline-offset-2 hover:underline"
        >
            {children}
        </Link>
    );
}

function IconDownload() {
    return (
        <svg
            aria-hidden="true"
            viewBox="0 0 20 20"
            fill="currentColor"
            className="h-5 w-5"
        >
            <path
                fillRule="evenodd"
                d="M10 3a.75.75 0 01.75.75v8.69l2.72-2.72a.75.75 0 111.06 1.06l-4 4a.75.75 0 01-1.06 0l-4-4a.75.75 0 111.06-1.06l2.72 2.72V3.75A.75.75 0 0110 3zM3.75 17a.75.75 0 000 1.5h12.5a.75.75 0 000-1.5H3.75z"
                clipRule="evenodd"
            />
        </svg>
    );
}

function IconEdit() {
    return (
        <svg
            aria-hidden="true"
            viewBox="0 0 20 20"
            fill="currentColor"
            className="h-5 w-5"
        >
            <path d="M17.414 2.586a2 2 0 010 2.828l-10 10A2 2 0 016 16H4a1 1 0 01-1-1v-2a2 2 0 01.586-1.414l10-10a2 2 0 012.828 0z" />
        </svg>
    );
}

function IconTrash() {
    return (
        <svg
            aria-hidden="true"
            viewBox="0 0 20 20"
            fill="currentColor"
            className="h-5 w-5"
        >
            <path
                fillRule="evenodd"
                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                clipRule="evenodd"
            />
        </svg>
    );
}

function IconShield() {
    return (
        <svg
            aria-hidden="true"
            viewBox="0 0 20 20"
            fill="currentColor"
            className="h-5 w-5"
        >
            <path
                fillRule="evenodd"
                d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317 4.66-1.647 8-6.092 8-11.317 0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zm3.707 7.767a1 1 0 00-1.414-1.414L9 11.586 7.707 10.29a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clipRule="evenodd"
            />
        </svg>
    );
}

function IconExchange() {
    return (
        <svg
            aria-hidden="true"
            viewBox="0 0 20 20"
            fill="currentColor"
            className="h-5 w-5"
        >
            <path
                fillRule="evenodd"
                d="M4 5a1 1 0 011-1h10.586l-1.293-1.293a1 1 0 111.414-1.414l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L15.586 6H5a1 1 0 01-1-1zm12 9a1 1 0 00-1 1H4.414l1.293 1.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 1.414L4.414 13H15a1 1 0 001 1z"
                clipRule="evenodd"
            />
        </svg>
    );
}

function IconMail() {
    return (
        <svg
            aria-hidden="true"
            viewBox="0 0 20 20"
            fill="currentColor"
            className="h-5 w-5"
        >
            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
        </svg>
    );
}
