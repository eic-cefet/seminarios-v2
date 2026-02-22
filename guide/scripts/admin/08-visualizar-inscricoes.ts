import type { Guide } from "../../lib/types.js";
import {
  loginAsAdmin,
  waitForContent,
  navigateAdmin,
} from "../../lib/helpers.js";

export default function visualizarInscricoes(): Guide {
  return {
    id: "admin-visualizar-inscricoes",
    title: "Como visualizar inscrições",
    description:
      "Como acompanhar as inscrições dos participantes nos seminários.",
    category: "admin",
    steps: [
      {
        title: "Acessar a lista de inscrições",
        description:
          'Faça login como administrador e clique em "Inscrições" no menu lateral.',
        screenshotName: "admin-inscricoes-01-lista",
        action: async (page) => {
          await loginAsAdmin(page);
          await navigateAdmin(page, "/registrations");
          await waitForContent(page);
        },
      },
      {
        title: "Visualizar inscrições",
        description:
          "A tabela mostra o nome do participante, seminário, data da inscrição e status de presença.",
        screenshotName: "admin-inscricoes-02-tabela",
        action: async (page) => {
          await waitForContent(page);
        },
      },
      {
        title: "Pesquisar e filtrar",
        description:
          "Use a barra de pesquisa para encontrar inscrições específicas por nome do participante ou seminário.",
        screenshotName: "admin-inscricoes-03-pesquisa",
        action: async (page) => {
          await page.fill("#search", "Estudante");
          await waitForContent(page);
        },
      },
    ],
  };
}
