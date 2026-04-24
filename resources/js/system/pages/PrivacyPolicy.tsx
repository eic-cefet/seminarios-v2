import { Link } from "react-router-dom";
import { PageTitle } from "@shared/components/PageTitle";
import { ROUTES } from "@shared/config/routes";
import { Layout } from "../components/Layout";

const DPO_EMAIL = "lgpd@cefet-rj.br";

export default function PrivacyPolicy() {
    return (
        <>
            <PageTitle title="Política de Privacidade" />
            <Layout>
                <article className="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8 prose prose-gray">
                    <h1>Política de Privacidade</h1>
                    <p>
                        Esta política descreve como a Escola de Informática e
                        Computação (EIC) do CEFET-RJ coleta, utiliza, armazena e
                        compartilha seus dados pessoais no <em>Sistema de
                        Seminários da EIC</em>, em conformidade com a Lei nº
                        13.709/2018 (LGPD).
                    </p>

                    <h2>1. Dados que coletamos</h2>
                    <ul>
                        <li>
                            <strong>Cadastro:</strong> nome, e-mail, senha
                            (armazenada com hash), curso, situação acadêmica e
                            vínculo (aluno, professor, outro).
                        </li>
                        <li>
                            <strong>Autenticação social:</strong> quando você
                            opta por entrar com Google ou GitHub, recebemos
                            nome, e-mail e identificador do provedor.
                        </li>
                        <li>
                            <strong>Participação:</strong> inscrições em
                            seminários, presença, certificados emitidos.
                        </li>
                        <li>
                            <strong>Feedback:</strong> avaliações e comentários
                            que você voluntariamente envia.
                        </li>
                        <li>
                            <strong>Técnicos:</strong> endereço IP,
                            <em> user-agent</em>, cookies essenciais e cookies
                            opcionais com seu consentimento.
                        </li>
                    </ul>

                    <h2>2. Finalidades e bases legais</h2>
                    <p>Tratamos seus dados nas seguintes hipóteses da LGPD (Art. 7):</p>
                    <ul>
                        <li>
                            <strong>Execução de contrato (V):</strong> gestão da
                            conta, inscrição em seminários e emissão de
                            certificados.
                        </li>
                        <li>
                            <strong>Legítimo interesse (IX):</strong> registro
                            de presença como documento acadêmico institucional,
                            segurança e prevenção de fraude.
                        </li>
                        <li>
                            <strong>Consentimento (I):</strong> cookies
                            opcionais, comunicações não essenciais, análise de
                            sentimento por IA em comentários.
                        </li>
                        <li>
                            <strong>Obrigação legal (II):</strong> logs de
                            auditoria de segurança por prazo legal e
                            institucional.
                        </li>
                    </ul>

                    <h2>3. Retenção dos dados</h2>
                    <ul>
                        <li>Logs de auditoria: 90 dias.</li>
                        <li>Sessões inativas: 30 dias após a última atividade.</li>
                        <li>Tokens de API não utilizados: 180 dias.</li>
                        <li>
                            Registros de presença e certificados: preservados
                            por tempo indeterminado como documentação acadêmica
                            institucional (Art. 16, II, LGPD).
                        </li>
                        <li>
                            Conta do usuário: mantida enquanto ativa. A
                            exclusão solicitada ocorre após período de carência
                            de 30 dias, via pseudonimização.
                        </li>
                    </ul>

                    <h2>4. Seus direitos (Art. 18 da LGPD)</h2>
                    <p>Como titular dos dados, você tem direito a:</p>
                    <ol>
                        <li>
                            <strong>Confirmação</strong> da existência de
                            tratamento.
                        </li>
                        <li>
                            <strong>Acesso</strong> aos dados — disponível via{" "}
                            <Link to={ROUTES.SYSTEM.DATA_RIGHTS}>
                                Central de Direitos
                            </Link>
                            .
                        </li>
                        <li>
                            <strong>Correção</strong> de dados incompletos ou
                            desatualizados — pela tela de perfil.
                        </li>
                        <li>
                            <strong>Anonimização</strong>, bloqueio ou{" "}
                            <strong>eliminação</strong> de dados desnecessários
                            ou tratados em desconformidade.
                        </li>
                        <li>
                            <strong>Portabilidade</strong> dos dados em formato
                            estruturado (JSON).
                        </li>
                        <li>
                            <strong>Eliminação</strong> de dados tratados com
                            seu consentimento, preservadas as hipóteses do Art.
                            16.
                        </li>
                        <li>
                            Informação sobre entidades com quem{" "}
                            <strong>compartilhamos</strong> dados (seção 5).
                        </li>
                        <li>
                            Informação sobre a possibilidade de não fornecer{" "}
                            <strong>consentimento</strong> e suas consequências.
                        </li>
                        <li>
                            <strong>Revogar o consentimento</strong> a qualquer
                            momento pela{" "}
                            <Link to={ROUTES.SYSTEM.COOKIE_PREFERENCES}>
                                Central de Preferências
                            </Link>
                            .
                        </li>
                    </ol>

                    <h2>5. Compartilhamento com terceiros</h2>
                    <ul>
                        <li>
                            <strong>AWS S3</strong> (Amazon Web Services):
                            armazenamento dos arquivos PDF/JPG dos certificados.
                            Contêm seu nome, o nome do seminário e a data.
                        </li>
                        <li>
                            <strong>OpenAI</strong>: somente quando você
                            autoriza explicitamente a análise de sentimento, o
                            texto do seu comentário é enviado ao modelo de IA
                            para classificação. A nota (1–5) também é enviada.
                        </li>
                        <li>
                            <strong>Google / GitHub</strong>: apenas quando
                            você opta por entrar via autenticação social.
                        </li>
                        <li>
                            <strong>Provedor de e-mail</strong>: utilizado para
                            enviar lembretes, certificados e notificações
                            transacionais.
                        </li>
                    </ul>

                    <h2>6. Cookies</h2>
                    <p>
                        Detalhes das categorias e como gerenciá-las estão na{" "}
                        <Link to={ROUTES.SYSTEM.COOKIE_PREFERENCES}>
                            Central de Preferências de Cookies
                        </Link>
                        .
                    </p>

                    <h2>7. Segurança</h2>
                    <p>
                        Adotamos medidas técnicas (HTTPS, hashing de senha,
                        tokens com expiração, <em>cookies</em> HttpOnly/SameSite,
                        pruning de logs, controle de acesso por papel) e
                        administrativas (registro de tratamento, política de
                        retenção) para proteger seus dados contra acesso,
                        divulgação, alteração ou destruição não autorizados.
                    </p>

                    <h2>8. Incidentes de segurança</h2>
                    <p>
                        Caso ocorra incidente de segurança que possa acarretar
                        risco ou dano relevante aos titulares, comunicaremos a
                        Autoridade Nacional de Proteção de Dados (ANPD) e os
                        titulares afetados em prazo razoável, nos termos do Art.
                        48 da LGPD.
                    </p>

                    <h2>9. Encarregado (DPO)</h2>
                    <p>
                        Dúvidas, reclamações ou solicitações relacionadas a esta
                        política podem ser encaminhadas ao nosso Encarregado
                        pelo Tratamento de Dados Pessoais:
                    </p>
                    <p>
                        <a href={`mailto:${DPO_EMAIL}`}>{DPO_EMAIL}</a>
                    </p>

                    <h2>10. Atualizações desta política</h2>
                    <p>
                        Podemos atualizar esta política a qualquer momento. A
                        versão vigente e a data da última atualização aparecem
                        abaixo; alterações materiais disparam nova solicitação
                        de consentimento.
                    </p>
                    <p className="text-sm text-gray-500">
                        Versão 1.0 — 23 de abril de 2026.
                    </p>
                </article>
            </Layout>
        </>
    );
}
