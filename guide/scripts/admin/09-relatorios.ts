import type { Guide } from "../../lib/types.js";
import {
  loginAsAdmin,
  waitForContent,
  navigateAdmin,
  scrollAdmin,
} from "../../lib/helpers.js";

export default function relatorios(): Guide {
  return {
    id: "admin-relatorios",
    title: "Como gerar relatórios semestrais",
    description:
      "Como acessar e visualizar os relatórios semestrais de atividades.",
    category: "admin",
    steps: [
      {
        title: "Acessar os relatórios",
        description:
          "Faça login como administrador e acesse Relatórios → Relatório Semestral no menu lateral.",
        screenshotName: "admin-relatorios-01-pagina",
        action: async (page) => {
          await loginAsAdmin(page);
          await navigateAdmin(page, "/reports/semestral");
          await waitForContent(page);
        },
      },
      {
        title: "Selecionar o semestre",
        description:
          "Clique no menu de semestre e escolha o período desejado.",
        screenshotName: "admin-relatorios-02-semestre",
        action: async (page) => {
          // Click the semester Radix Select trigger
          const semesterTrigger = page
            .locator('[role="combobox"]')
            .filter({ hasText: "Selecione o semestre" });
          await semesterTrigger.click();
          await page.waitForSelector('[role="option"]', {
            state: "visible",
            timeout: 5000,
          });
          // Select the first semester (most recent)
          await page.locator('[role="option"]').first().click();
          await waitForContent(page);
        },
      },
      {
        title: "Marcar situações do curso",
        description:
          'Na seção "Situação do Curso", marque as opções desejadas como filtro adicional.',
        screenshotName: "admin-relatorios-03-situacoes",
        action: async (page) => {
          await scrollAdmin(page, 400);
          await waitForContent(page);

          // Check "Cursando" checkbox
          await page.click('#situation-Cursando');
          await waitForContent(page);
        },
      },
      {
        title: "Gerar o relatório",
        description:
          'Clique em "Visualizar Relatório" para gerar os dados no navegador.',
        screenshotName: "admin-relatorios-04-gerar",
        action: async (page) => {
          await scrollAdmin(page, 600);
          await waitForContent(page);

          // Click the submit button
          await page.click('button[type="submit"]');

          // Wait for the report to load (summary cards appear)
          try {
            await page.waitForSelector('text="Total de Usuários"', {
              state: "visible",
              timeout: 15000,
            });
          } catch {
            // Report may return empty — that's fine
          }
          await waitForContent(page);
        },
      },
      {
        title: "Visualizar resultados",
        description:
          "O relatório exibe cards com o total de usuários, horas e o semestre selecionado.",
        screenshotName: "admin-relatorios-05-resultados",
        action: async (page) => {
          await scrollAdmin(page, 700);
          await waitForContent(page);
        },
      },
      {
        title: "Tabela de participantes",
        description:
          "Role para ver a tabela com os participantes, seus cursos, horas e número de apresentações. Clique em uma linha para expandir os detalhes.",
        screenshotName: "admin-relatorios-06-tabela",
        action: async (page) => {
          await scrollAdmin(page, 999);
          await waitForContent(page);

          // Try to expand the first row to show details
          const firstRow = page.locator('table tbody tr').first();
          if (await firstRow.isVisible().catch(() => false)) {
            await firstRow.click();
            await waitForContent(page);
          }
        },
      },
    ],
  };
}
