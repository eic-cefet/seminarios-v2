import type { Guide } from "../../lib/types.js";
import { waitForContent } from "../../lib/helpers.js";

export default function navegarSeminarios(): Guide {
  return {
    id: "navegar-seminarios",
    title: "Como navegar pelos seminários",
    description:
      "Como encontrar seminários por tópico, visualizar apresentações e acessar detalhes.",
    category: "usuario",
    steps: [
      {
        title: "Página inicial",
        description:
          "A página inicial exibe os próximos seminários, estatísticas e tópicos em destaque.",
        screenshotName: "usuario-navegar-01-home",
        action: async (page) => {
          await page.goto("/");
          await waitForContent(page);
        },
      },
      {
        title: "Explorar tópicos",
        description:
          'Clique em "Tópicos" no menu para ver todos os assuntos disponíveis e quantos seminários cada um possui.',
        screenshotName: "usuario-navegar-02-topicos",
        action: async (page) => {
          await page.goto("/topicos");
          await waitForContent(page);
        },
      },
      {
        title: "Ver todas as apresentações",
        description:
          'Acesse "Apresentações" no menu para ver todos os seminários. Use os filtros para encontrar o que procura.',
        screenshotName: "usuario-navegar-03-apresentacoes",
        action: async (page) => {
          await page.goto("/apresentacoes");
          await waitForContent(page);
        },
      },
      {
        title: "Detalhes de um seminário",
        description:
          "Clique em qualquer seminário para ver informações completas: descrição, palestrantes, data, local e opção de inscrição.",
        screenshotName: "usuario-navegar-04-detalhe",
        action: async (page) => {
          // Click on the first seminar card/link available
          const seminarLink = page
            .locator('a[href*="/seminario/"]')
            .first();
          if (await seminarLink.isVisible()) {
            await seminarLink.click();
          } else {
            // Fallback: go to presentations and find one
            await page.goto("/apresentacoes");
            await waitForContent(page);
            const link = page.locator('a[href*="/seminario/"]').first();
            await link.click();
          }
          await page.waitForLoadState("networkidle");
          await waitForContent(page);
        },
      },
      {
        title: "Ver workshops",
        description:
          'Acesse "Workshops" no menu para ver os workshops disponíveis e seus seminários associados.',
        screenshotName: "usuario-navegar-05-workshops",
        action: async (page) => {
          await page.goto("/workshops");
          await waitForContent(page);
        },
      },
    ],
  };
}
