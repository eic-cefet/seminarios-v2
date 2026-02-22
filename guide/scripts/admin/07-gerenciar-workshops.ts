import type { Guide } from "../../lib/types.js";
import {
  loginAsAdmin,
  waitForContent,
  navigateAdmin,
} from "../../lib/helpers.js";

export default function gerenciarWorkshops(): Guide {
  return {
    id: "admin-gerenciar-workshops",
    title: "Como gerenciar workshops",
    description:
      "Como criar e gerenciar workshops que agrupam múltiplos seminários.",
    category: "admin",
    steps: [
      {
        title: "Acessar a lista de workshops",
        description:
          "Faça login como administrador e acesse Seminários → Workshops no menu lateral.",
        screenshotName: "admin-workshops-01-lista",
        action: async (page) => {
          await loginAsAdmin(page);
          await navigateAdmin(page, "/workshops");
          await waitForContent(page);
        },
      },
      {
        title: "Visualizar workshops",
        description:
          "A tabela exibe todos os workshops com descrição e quantidade de seminários associados.",
        screenshotName: "admin-workshops-02-listagem",
        action: async (page) => {
          await waitForContent(page);
        },
      },
      {
        title: 'Clicar em "Novo Workshop"',
        description:
          'Clique no botão "Novo Workshop" para abrir o formulário de criação.',
        screenshotName: "admin-workshops-03-botao-novo",
        action: async (page) => {
          await page.click('button:has-text("Novo Workshop")');
          await page.waitForSelector('[role="dialog"]', {
            state: "visible",
            timeout: 5000,
          });
          await waitForContent(page);
        },
      },
      {
        title: "Preencher os dados do workshop",
        description:
          "Informe o nome e a descrição do workshop. Você também pode associar seminários existentes.",
        screenshotName: "admin-workshops-04-form-preenchido",
        action: async (page) => {
          await page.fill('[role="dialog"] #name', "Workshop de Machine Learning");

          // Fill the MarkdownEditor description textarea
          const textarea = page.locator(
            '[role="dialog"] textarea[placeholder="Escreva a descrição em Markdown..."]',
          );
          await textarea.fill(
            "Workshop introdutório sobre conceitos e aplicações de **Machine Learning** na engenharia.",
          );
          await waitForContent(page);
        },
      },
    ],
  };
}
