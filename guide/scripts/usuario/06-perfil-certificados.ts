import type { Guide } from "../../lib/types.js";
import { loginAsStudent, waitForContent } from "../../lib/helpers.js";

export default function perfilCertificados(): Guide {
  return {
    id: "perfil-certificados",
    title: "Como acessar o perfil e certificados",
    description:
      "Como visualizar e editar seu perfil, ver inscrições e baixar certificados.",
    category: "usuario",
    steps: [
      {
        title: "Fazer login",
        description: "Acesse sua conta no sistema.",
        screenshotName: "usuario-perfil-01-login",
        action: async (page) => {
          await loginAsStudent(page);
          await waitForContent(page);
        },
      },
      {
        title: "Acessar o perfil",
        description:
          "Clique no seu nome no menu superior e selecione \"Perfil\" no menu dropdown.",
        screenshotName: "usuario-perfil-02-menu",
        action: async (page) => {
          await page.goto("/perfil");
          await page.waitForLoadState("networkidle");
          await waitForContent(page);
        },
      },
      {
        title: "Visualizar dados do perfil",
        description:
          "Aqui você pode ver e editar suas informações pessoais e dados acadêmicos.",
        screenshotName: "usuario-perfil-03-dados",
        action: async (page) => {
          await waitForContent(page);
        },
      },
      {
        title: "Ver inscrições",
        description:
          "Role a página para ver a seção de inscrições com seus seminários registrados.",
        screenshotName: "usuario-perfil-04-inscricoes",
        action: async (page) => {
          await page.evaluate(() => window.scrollTo(0, 600));
          await waitForContent(page);
        },
      },
      {
        title: "Ver certificados",
        description:
          "Continue rolando para ver a seção de certificados dos seminários que você participou.",
        screenshotName: "usuario-perfil-05-certificados",
        action: async (page) => {
          await page.evaluate(() => window.scrollTo(0, 1200));
          await waitForContent(page);
        },
      },
    ],
  };
}
