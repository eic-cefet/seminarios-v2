import type { ReactNode } from "react";
import { Link } from "react-router-dom";
import { PageTitle } from "@shared/components/PageTitle";
import { ROUTES } from "@shared/config/routes";
import { Layout } from "../components/Layout";

export default function TermsOfService() {
    return (
        <>
            <PageTitle title="Termos de Uso" />
            <Layout>
                <header className="border-b border-gray-200 bg-gradient-to-b from-primary-50 to-white">
                    <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
                        <p className="text-xs font-semibold uppercase tracking-wider text-primary-700">
                            CEFET-RJ · EIC
                        </p>
                        <h1 className="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                            Termos de Uso
                        </h1>
                        <p className="mt-4 text-base leading-relaxed text-gray-600">
                            Estes Termos regulam o acesso e a utilização do
                            Sistema de Seminários da Escola de Informática e
                            Computação. Leia com atenção antes de criar sua
                            conta.
                        </p>
                        <p className="mt-4 text-xs text-gray-500">
                            Versão 1.0 · Última atualização em 23 de abril de
                            2026.
                        </p>
                    </div>
                </header>

                <article className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
                    <div className="space-y-10">
                        <Section number="1" title="Aceitação">
                            <p className="text-base leading-relaxed text-gray-700">
                                Ao utilizar o Sistema de Seminários da EIC,
                                você aceita estes Termos e a{" "}
                                <Link
                                    to={ROUTES.SYSTEM.PRIVACY_POLICY}
                                    className="font-medium text-primary-700 underline-offset-2 hover:underline"
                                >
                                    Política de Privacidade
                                </Link>
                                . Se não concordar, não utilize o sistema.
                            </p>
                        </Section>

                        <Section number="2" title="Cadastro">
                            <p className="text-base leading-relaxed text-gray-700">
                                Você deve fornecer informações verdadeiras e
                                mantê-las atualizadas. O cadastro é gratuito e
                                destina-se a alunos, docentes e público
                                interessado nas atividades da EIC.
                            </p>
                        </Section>

                        <Section number="3" title="Conduta">
                            <ul className="space-y-2.5">
                                <RuleItem>
                                    Não utilizar o sistema para fins ilícitos
                                    ou contrários à ordem pública.
                                </RuleItem>
                                <RuleItem>
                                    Não tentar acessar áreas restritas ou dados
                                    de terceiros sem autorização.
                                </RuleItem>
                                <RuleItem>
                                    Respeitar palestrantes e demais
                                    participantes nos comentários e avaliações.
                                </RuleItem>
                            </ul>
                        </Section>

                        <Section number="4" title="Propriedade intelectual">
                            <p className="text-base leading-relaxed text-gray-700">
                                O conteúdo apresentado nas apresentações pertence
                                a seus respectivos autores. O sistema e sua
                                marca pertencem ao CEFET-RJ. Certificados
                                emitidos comprovam presença e carga horária e
                                podem ser verificados publicamente.
                            </p>
                        </Section>

                        <Section number="5" title="Limitação de responsabilidade">
                            <p className="text-base leading-relaxed text-gray-700">
                                O sistema é fornecido &ldquo;como está&rdquo;.
                                Eventuais indisponibilidades não geram
                                obrigação de indenizar. Faremos esforços
                                razoáveis para manter o serviço disponível e
                                corrigir problemas em tempo hábil.
                            </p>
                        </Section>

                        <Section number="6" title="Lei aplicável e foro">
                            <p className="text-base leading-relaxed text-gray-700">
                                Estes Termos são regidos pelas leis da
                                República Federativa do Brasil. Fica eleito o
                                foro da Justiça Federal do Rio de Janeiro para
                                dirimir controvérsias.
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

function RuleItem({ children }: { children: ReactNode }) {
    return (
        <li className="flex gap-3 rounded-md border border-gray-200 bg-white px-4 py-3 text-sm leading-relaxed text-gray-700">
            <svg
                aria-hidden="true"
                viewBox="0 0 20 20"
                fill="currentColor"
                className="mt-0.5 h-5 w-5 shrink-0 text-primary-600"
            >
                <path
                    fillRule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clipRule="evenodd"
                />
            </svg>
            <span>{children}</span>
        </li>
    );
}
