import { Link } from "react-router-dom";
import { PageTitle } from "@shared/components/PageTitle";
import { ROUTES } from "@shared/config/routes";
import { Layout } from "../components/Layout";

export default function TermsOfService() {
    return (
        <>
            <PageTitle title="Termos de Uso" />
            <Layout>
                <article className="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8 prose prose-gray">
                    <h1>Termos de Uso</h1>

                    <h2>1. Aceitação</h2>
                    <p>
                        Ao utilizar o Sistema de Seminários da EIC, você aceita
                        estes Termos e a{" "}
                        <Link to={ROUTES.SYSTEM.PRIVACY_POLICY}>
                            Política de Privacidade
                        </Link>
                        . Se não concordar, não utilize o sistema.
                    </p>

                    <h2>2. Cadastro</h2>
                    <p>
                        Você deve fornecer informações verdadeiras e mantê-las
                        atualizadas. O cadastro é gratuito e destina-se a alunos,
                        docentes e público interessado nas atividades da EIC.
                    </p>

                    <h2>3. Conduta</h2>
                    <ul>
                        <li>
                            Não utilizar o sistema para fins ilícitos ou
                            contrários à ordem pública.
                        </li>
                        <li>
                            Não tentar acessar áreas restritas ou dados de
                            terceiros sem autorização.
                        </li>
                        <li>
                            Respeitar palestrantes e demais participantes nos
                            comentários e avaliações.
                        </li>
                    </ul>

                    <h2>4. Propriedade intelectual</h2>
                    <p>
                        O conteúdo apresentado nos seminários pertence a seus
                        respectivos autores. O sistema e sua marca pertencem ao
                        CEFET-RJ. Certificados emitidos comprovam presença e
                        carga horária e podem ser verificados publicamente.
                    </p>

                    <h2>5. Limitação de responsabilidade</h2>
                    <p>
                        O sistema é fornecido "como está". Eventuais
                        indisponibilidades não geram obrigação de indenizar.
                        Faremos esforços razoáveis para manter o serviço
                        disponível e corrigir problemas em tempo hábil.
                    </p>

                    <h2>6. Lei aplicável e foro</h2>
                    <p>
                        Estes Termos são regidos pelas leis da República
                        Federativa do Brasil. Fica eleito o foro da Justiça
                        Federal do Rio de Janeiro para dirimir controvérsias.
                    </p>

                    <p className="text-sm text-gray-500">
                        Versão 1.0 — 23 de abril de 2026.
                    </p>
                </article>
            </Layout>
        </>
    );
}
