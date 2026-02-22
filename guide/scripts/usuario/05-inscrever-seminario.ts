import type { Guide } from "../../lib/types.js";
import { loginAsStudent, waitForContent } from "../../lib/helpers.js";

export default function inscreverSeminario(): Guide {
  return {
    id: "inscrever-seminario",
    title: "Como se inscrever em um seminário",
    description:
      "Como se inscrever em um seminário disponível no sistema.",
    category: "usuario",
    steps: [
      {
        title: "Fazer login",
        description:
          "Primeiro, faça login na sua conta para poder se inscrever.",
        screenshotName: "usuario-inscrever-01-login",
        action: async (page) => {
          await loginAsStudent(page);
          await waitForContent(page);
        },
      },
      {
        title: "Encontrar um seminário",
        description:
          "Navegue até as apresentações e encontre um seminário futuro disponível para inscrição.",
        screenshotName: "usuario-inscrever-02-apresentacoes",
        action: async (page) => {
          await page.goto("/apresentacoes");
          await waitForContent(page);
        },
      },
      {
        title: "Acessar o seminário",
        description:
          "Clique no seminário desejado para ver seus detalhes e a opção de inscrição.",
        screenshotName: "usuario-inscrever-03-detalhe",
        action: async (page) => {
          const seminarLink = page
            .locator('a[href*="/seminario/"]')
            .first();
          await seminarLink.click();
          await page.waitForLoadState("networkidle");
          await waitForContent(page);
        },
      },
      {
        title: 'Clicar em "Inscrever-se"',
        description:
          'Clique no botão "Inscrever-se" para confirmar sua participação no seminário.',
        screenshotName: "usuario-inscrever-04-botao",
        action: async (page) => {
          // Screenshot the page showing the register button
          await waitForContent(page);
        },
      },
    ],
  };
}
