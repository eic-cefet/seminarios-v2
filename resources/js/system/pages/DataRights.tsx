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
                <article className="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8 prose prose-gray">
                    <h1>Central de Direitos LGPD</h1>
                    <p>
                        A LGPD (Lei 13.709/2018) garante aos titulares dos dados
                        uma série de direitos. Abaixo você encontra como exercer
                        cada um deles no Sistema de Seminários da EIC.
                    </p>

                    <h2>Exportar seus dados</h2>
                    <p>
                        Você pode baixar um arquivo <code>.zip</code> contendo
                        todos os dados que mantemos sobre você, em formato JSON
                        estruturado. A opção fica em{" "}
                        <strong>
                            Perfil → Privacidade → Exportar meus dados
                        </strong>
                        . O arquivo é enviado por e-mail e fica disponível por
                        2 horas.
                    </p>

                    <h2>Corrigir dados incompletos ou desatualizados</h2>
                    <p>
                        Edite suas informações na tela de{" "}
                        <Link to={ROUTES.SYSTEM.PROFILE}>Perfil</Link>.
                    </p>

                    <h2>Excluir sua conta</h2>
                    <p>
                        Você pode solicitar a exclusão da sua conta em{" "}
                        <strong>
                            Perfil → Privacidade → Excluir minha conta
                        </strong>
                        . O processo inclui um período de carência de 30 dias
                        durante o qual você pode cancelar a solicitação apenas
                        fazendo login novamente. Após o prazo, seus dados são
                        pseudonimizados: nome e e-mail são substituídos por
                        valores genéricos, mas registros acadêmicos de presença
                        são preservados como documentação institucional
                        (legítimo interesse, Art. 16, II, LGPD).
                    </p>

                    <h2>Gerenciar consentimentos</h2>
                    <p>
                        Revogue ou conceda consentimentos (cookies, análise de
                        IA em comentários) na{" "}
                        <Link to={ROUTES.SYSTEM.COOKIE_PREFERENCES}>
                            Central de Preferências
                        </Link>{" "}
                        ou diretamente nos respectivos formulários.
                    </p>

                    <h2>Portabilidade para outro serviço</h2>
                    <p>
                        O arquivo JSON exportado acima já atende ao direito de
                        portabilidade previsto no Art. 18, V da LGPD.
                    </p>

                    <h2>Fazer uma solicitação formal</h2>
                    <p>
                        Para qualquer solicitação que não possa ser cumprida
                        pelas opções acima, entre em contato com nosso
                        Encarregado pelo Tratamento de Dados Pessoais:
                    </p>
                    <p>
                        <a href={`mailto:${DPO_EMAIL}`}>{DPO_EMAIL}</a>
                    </p>
                    <p>
                        Responderemos em prazo razoável, nos termos da LGPD. Se
                        considerar a resposta insatisfatória, você pode também
                        apresentar reclamação à{" "}
                        <a
                            href="https://www.gov.br/anpd/"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Autoridade Nacional de Proteção de Dados (ANPD)
                        </a>
                        .
                    </p>
                </article>
            </Layout>
        </>
    );
}
