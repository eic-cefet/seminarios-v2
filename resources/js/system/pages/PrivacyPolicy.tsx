import type { ReactNode } from "react";
import { Link } from "react-router-dom";
import { PageTitle } from "@shared/components/PageTitle";
import { ROUTES } from "@shared/config/routes";
import { Layout } from "../components/Layout";

const DPO_EMAIL = "lgpd@eic-seminarios.com";

export default function PrivacyPolicy() {
    return (
        <>
            <PageTitle title="Política de Privacidade" />
            <Layout>
                <header className="border-b border-gray-200 bg-gradient-to-b from-primary-50 to-white">
                    <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
                        <p className="text-xs font-semibold uppercase tracking-wider text-primary-700">
                            LGPD · Lei nº 13.709/2018
                        </p>
                        <h1 className="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                            Política de Privacidade
                        </h1>
                        <p className="mt-4 text-base leading-relaxed text-gray-600">
                            Esta política descreve como a Escola de Informática
                            e Computação (EIC) do CEFET-RJ coleta, utiliza,
                            armazena e compartilha seus dados pessoais no{" "}
                            <em>Sistema de Seminários da EIC</em>, em
                            conformidade com a Lei nº 13.709/2018 (LGPD).
                        </p>
                        <p className="mt-4 text-xs text-gray-500">
                            Versão 1.0 · Última atualização em 23 de abril de
                            2026.
                        </p>
                    </div>
                </header>

                <article className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
                    <div className="space-y-12">
                        <Section number="1" title="Dados que coletamos">
                            <BulletList
                                items={[
                                    {
                                        label: "Cadastro",
                                        body: "nome, e-mail, senha (armazenada com hash), curso, situação acadêmica e vínculo (aluno, professor, outro).",
                                    },
                                    {
                                        label: "Autenticação social",
                                        body: "quando você opta por entrar com Google ou GitHub, recebemos nome, e-mail e identificador do provedor.",
                                    },
                                    {
                                        label: "Participação",
                                        body: "inscrições em seminários, presença, certificados emitidos.",
                                    },
                                    {
                                        label: "Feedback",
                                        body: "avaliações e comentários que você voluntariamente envia.",
                                    },
                                    {
                                        label: "Técnicos",
                                        body: (
                                            <>
                                                endereço IP, <em>user-agent</em>
                                                , cookies essenciais e cookies
                                                opcionais com seu consentimento.
                                            </>
                                        ),
                                    },
                                ]}
                            />
                        </Section>

                        <Section number="2" title="Finalidades e bases legais">
                            <p className="text-base leading-relaxed text-gray-700">
                                Tratamos seus dados nas seguintes hipóteses da
                                LGPD (Art. 7):
                            </p>
                            <BulletList
                                className="mt-4"
                                items={[
                                    {
                                        label: "Execução de contrato (V)",
                                        body: "gestão da conta, inscrição em seminários e emissão de certificados.",
                                    },
                                    {
                                        label: "Legítimo interesse (IX)",
                                        body: "registro de presença como documento acadêmico institucional, segurança e prevenção de fraude.",
                                    },
                                    {
                                        label: "Consentimento (I)",
                                        body: "cookies opcionais, comunicações não essenciais, análise de sentimento por IA em comentários.",
                                    },
                                    {
                                        label: "Obrigação legal (II)",
                                        body: "logs de auditoria de segurança por prazo legal e institucional.",
                                    },
                                ]}
                            />
                        </Section>

                        <Section number="3" title="Retenção dos dados">
                            <dl className="grid gap-3 sm:grid-cols-2">
                                <RetentionItem
                                    label="Logs de auditoria"
                                    value="90 dias"
                                />
                                <RetentionItem
                                    label="Sessões inativas"
                                    value="30 dias após última atividade"
                                />
                                <RetentionItem
                                    label="Tokens de API não utilizados"
                                    value="180 dias"
                                />
                                <RetentionItem
                                    label="Exclusão de conta"
                                    value="30 dias de carência, depois pseudonimização"
                                />
                                <div className="sm:col-span-2 rounded-lg border border-gray-200 bg-gray-50/50 px-4 py-3">
                                    <dt className="text-sm font-semibold text-gray-900">
                                        Registros de presença e certificados
                                    </dt>
                                    <dd className="mt-1 text-sm text-gray-600">
                                        Preservados por tempo indeterminado como
                                        documentação acadêmica institucional
                                        (Art. 16, II, LGPD).
                                    </dd>
                                </div>
                            </dl>
                        </Section>

                        <Section
                            number="4"
                            title="Seus direitos (Art. 18 da LGPD)"
                        >
                            <p className="text-base leading-relaxed text-gray-700">
                                Como titular dos dados, você tem direito a:
                            </p>
                            <ol className="mt-4 space-y-3 text-base text-gray-700">
                                <RightsItem n={1}>
                                    <strong>Confirmação</strong> da existência
                                    de tratamento.
                                </RightsItem>
                                <RightsItem n={2}>
                                    <strong>Acesso</strong> aos dados —
                                    disponível via{" "}
                                    <InlineLink to={ROUTES.SYSTEM.DATA_RIGHTS}>
                                        Central de Direitos
                                    </InlineLink>
                                    .
                                </RightsItem>
                                <RightsItem n={3}>
                                    <strong>Correção</strong> de dados
                                    incompletos ou desatualizados — pela tela de
                                    perfil.
                                </RightsItem>
                                <RightsItem n={4}>
                                    <strong>Anonimização</strong>, bloqueio ou{" "}
                                    <strong>eliminação</strong> de dados
                                    desnecessários ou tratados em
                                    desconformidade.
                                </RightsItem>
                                <RightsItem n={5}>
                                    <strong>Portabilidade</strong> dos dados em
                                    formato estruturado (JSON).
                                </RightsItem>
                                <RightsItem n={6}>
                                    <strong>Eliminação</strong> de dados
                                    tratados com seu consentimento, preservadas
                                    as hipóteses do Art. 16.
                                </RightsItem>
                                <RightsItem n={7}>
                                    Informação sobre entidades com quem{" "}
                                    <strong>compartilhamos</strong> dados (seção
                                    5).
                                </RightsItem>
                                <RightsItem n={8}>
                                    Informação sobre a possibilidade de não
                                    fornecer <strong>consentimento</strong> e
                                    suas consequências.
                                </RightsItem>
                                <RightsItem n={9}>
                                    <strong>Revogar o consentimento</strong> a
                                    qualquer momento pela{" "}
                                    <InlineLink
                                        to={ROUTES.SYSTEM.COOKIE_PREFERENCES}
                                    >
                                        Central de Preferências
                                    </InlineLink>
                                    .
                                </RightsItem>
                            </ol>
                        </Section>

                        <Section
                            number="5"
                            title="Compartilhamento com terceiros"
                        >
                            <BulletList
                                items={[
                                    {
                                        label: "AWS S3 (Amazon Web Services)",
                                        body: "armazenamento dos arquivos PDF/JPG dos certificados. Contêm seu nome, o nome do seminário e a data.",
                                    },
                                    {
                                        label: "OpenAI",
                                        body: "somente quando você autoriza explicitamente a análise de sentimento, o texto do seu comentário é enviado ao modelo de IA para classificação. A nota (1–5) também é enviada.",
                                    },
                                    {
                                        label: "Google / GitHub",
                                        body: "apenas quando você opta por entrar via autenticação social.",
                                    },
                                    {
                                        label: "Provedor de e-mail",
                                        body: "utilizado para enviar lembretes, certificados e notificações transacionais.",
                                    },
                                ]}
                            />
                        </Section>

                        <Section number="6" title="Cookies">
                            <p className="text-base leading-relaxed text-gray-700">
                                Detalhes das categorias e como gerenciá-las
                                estão na{" "}
                                <InlineLink
                                    to={ROUTES.SYSTEM.COOKIE_PREFERENCES}
                                >
                                    Central de Preferências de Cookies
                                </InlineLink>
                                .
                            </p>
                        </Section>

                        <Section number="7" title="Segurança">
                            <p className="text-base leading-relaxed text-gray-700">
                                Adotamos medidas técnicas (HTTPS, hashing de
                                senha, tokens com expiração, <em>cookies</em>{" "}
                                HttpOnly/SameSite, pruning de logs, controle de
                                acesso por papel) e administrativas (registro de
                                tratamento, política de retenção) para proteger
                                seus dados contra acesso, divulgação, alteração
                                ou destruição não autorizados.
                            </p>
                        </Section>

                        <Section number="8" title="Incidentes de segurança">
                            <p className="text-base leading-relaxed text-gray-700">
                                Caso ocorra incidente de segurança que possa
                                acarretar risco ou dano relevante aos titulares,
                                comunicaremos a Autoridade Nacional de Proteção
                                de Dados (ANPD) e os titulares afetados em prazo
                                razoável, nos termos do Art. 48 da LGPD.
                            </p>
                        </Section>

                        <Section number="9" title="Encarregado (DPO)">
                            <p className="text-base leading-relaxed text-gray-700">
                                Dúvidas, reclamações ou solicitações
                                relacionadas a esta política podem ser
                                encaminhadas ao nosso Encarregado pelo
                                Tratamento de Dados Pessoais:
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
                        </Section>

                        <Section
                            number="10"
                            title="Atualizações desta política"
                        >
                            <p className="text-base leading-relaxed text-gray-700">
                                Podemos atualizar esta política a qualquer
                                momento. A versão vigente e a data da última
                                atualização aparecem abaixo; alterações
                                materiais disparam nova solicitação de
                                consentimento.
                            </p>
                        </Section>
                    </div>

                    <footer className="mt-16 border-t border-gray-200 pt-6">
                        <p className="text-sm text-gray-500">
                            Versão 1.0 — 23 de abril de 2026.
                        </p>
                    </footer>
                </article>
            </Layout>
        </>
    );
}

function Section({
    number,
    title,
    children,
}: {
    number: string;
    title: string;
    children: ReactNode;
}) {
    return (
        <section>
            <div className="flex items-center gap-3">
                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700">
                    {number}
                </span>
                <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                    {title}
                </h2>
            </div>
            <div className="mt-4">{children}</div>
        </section>
    );
}

function BulletList({
    items,
    className,
}: {
    items: Array<{ label: string; body: ReactNode }>;
    className?: string;
}) {
    return (
        <ul className={`space-y-3 ${className ?? ""}`}>
            {items.map((item, i) => (
                <li
                    key={i}
                    className="relative rounded-md border border-gray-200 bg-white px-4 py-3 text-sm leading-relaxed text-gray-700"
                >
                    <strong className="block text-sm font-semibold text-gray-900">
                        {item.label}
                    </strong>
                    <span className="mt-1 block text-gray-600">
                        {item.body}
                    </span>
                </li>
            ))}
        </ul>
    );
}

function RetentionItem({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-lg border border-gray-200 px-4 py-3">
            <dt className="text-xs font-medium uppercase tracking-wider text-gray-500">
                {label}
            </dt>
            <dd className="mt-1 text-sm font-semibold text-gray-900">
                {value}
            </dd>
        </div>
    );
}

function RightsItem({ n, children }: { n: number; children: ReactNode }) {
    return (
        <li className="flex gap-3 leading-relaxed">
            <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600">
                {n}
            </span>
            <span className="flex-1">{children}</span>
        </li>
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
