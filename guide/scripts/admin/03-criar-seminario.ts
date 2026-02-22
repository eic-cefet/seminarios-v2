import type { Guide } from "../../lib/types.js";
import {
  loginAsAdmin,
  waitForContent,
  navigateAdmin,
  scrollAdmin,
} from "../../lib/helpers.js";

export default function criarSeminario(): Guide {
  return {
    id: "admin-criar-seminario",
    title: "Como criar um novo seminário",
    description:
      "Passo a passo para criar e publicar um novo seminário no sistema.",
    category: "admin",
    steps: [
      {
        title: "Acessar a lista de seminários",
        description:
          "Faça login como administrador e acesse Seminários no menu lateral.",
        screenshotName: "admin-criar-sem-01-lista",
        action: async (page) => {
          await loginAsAdmin(page);
          await navigateAdmin(page, "/seminars");
          await waitForContent(page);
        },
      },
      {
        title: 'Clicar em "Novo Seminário"',
        description:
          'Clique no botão "Novo Seminário" no canto superior direito da página.',
        screenshotName: "admin-criar-sem-02-botao-novo",
        action: async (page) => {
          await waitForContent(page);
        },
      },
      {
        title: "Formulário de criação",
        description:
          "Você será redirecionado para o formulário de criação com os campos vazios.",
        screenshotName: "admin-criar-sem-03-form-vazio",
        action: async (page) => {
          await navigateAdmin(page, "/seminars/new");
          await waitForContent(page, "#name");
        },
      },
      {
        title: "Preencher o nome do seminário",
        description:
          "Informe o nome do seminário. O slug será gerado automaticamente.",
        screenshotName: "admin-criar-sem-04-nome",
        action: async (page) => {
          await page.fill("#name", "Introdução à Inteligência Artificial");
          await waitForContent(page);
        },
      },
      {
        title: "Escrever a descrição",
        description:
          "Escreva a descrição do seminário usando Markdown. Você pode alternar entre escrever e visualizar.",
        screenshotName: "admin-criar-sem-05-descricao",
        action: async (page) => {
          const textarea = page.locator(
            'textarea[placeholder="Escreva a descrição em Markdown..."]',
          );
          await textarea.fill(
            "## Sobre o seminário\n\nEste seminário apresentará os conceitos fundamentais de **Inteligência Artificial** e suas aplicações práticas na engenharia.\n\n### Tópicos abordados\n- Machine Learning\n- Redes Neurais\n- Processamento de Linguagem Natural",
          );
          await waitForContent(page);
        },
      },
      {
        title: "Definir data e hora",
        description:
          "No card de agendamento à direita, defina a data e hora do seminário.",
        screenshotName: "admin-criar-sem-06-data",
        action: async (page) => {
          await page.fill("#scheduled_at", "2026-03-15T14:00");
          await waitForContent(page);
        },
      },
      {
        title: "Selecionar o local",
        description:
          "Escolha o local onde o seminário será realizado no menu suspenso.",
        screenshotName: "admin-criar-sem-07-local",
        action: async (page) => {
          await scrollAdmin(page, 500);
          await page.waitForTimeout(300);

          // Click the "Selecione um local" trigger
          const locationTrigger = page
            .locator('[role="combobox"]')
            .filter({ hasText: "Selecione um local" });
          await locationTrigger.click();
          await page.waitForSelector('[role="option"]', {
            state: "visible",
            timeout: 5000,
          });
          // Select the first location
          await page.locator('[role="option"]').first().click();
          await waitForContent(page);
        },
      },
      {
        title: "Selecionar o tipo de seminário",
        description:
          "Escolha o tipo de apresentação (Seminário, TCC, Dissertação, etc.).",
        screenshotName: "admin-criar-sem-08-tipo",
        action: async (page) => {
          // Click the type trigger (shows "Nenhum tipo" by default)
          const typeTrigger = page
            .locator('[role="combobox"]')
            .filter({ hasText: "Nenhum tipo" });
          await typeTrigger.click();
          await page.waitForSelector('[role="option"]', {
            state: "visible",
            timeout: 5000,
          });
          // Select "Seminário" (second option, first is "Nenhum tipo")
          await page
            .locator('[role="option"]')
            .filter({ hasText: "Seminário" })
            .click();
          await waitForContent(page);
        },
      },
      {
        title: "Adicionar tópicos",
        description:
          "Digite o nome de um tópico no campo de pesquisa e selecione da lista ou pressione Enter para criar um novo.",
        screenshotName: "admin-criar-sem-09-topicos",
        action: async (page) => {
          await scrollAdmin(page, 700);
          await page.waitForTimeout(300);

          // Type in the subject search input
          const subjectInput = page.locator(
            'input[placeholder="Digite e pressione Enter..."]',
          );
          await subjectInput.fill("Inteligência");
          await page.waitForTimeout(800);

          // Click first suggestion if available
          const suggestion = page
            .locator(".cursor-pointer .text-sm")
            .first();
          if (await suggestion.isVisible({ timeout: 2000 }).catch(() => false)) {
            await suggestion.click();
          } else {
            // Press Enter to create new topic
            await subjectInput.press("Enter");
          }
          await waitForContent(page);
        },
      },
      {
        title: "Selecionar palestrantes",
        description:
          'Clique em "Selecionar Palestrantes" para abrir o modal de seleção.',
        screenshotName: "admin-criar-sem-10-palestrantes-botao",
        action: async (page) => {
          await scrollAdmin(page, 999);
          await page.waitForTimeout(300);

          // Click "Selecionar Palestrantes" button
          await page.click('button:has-text("Selecionar Palestrantes")');
          await page.waitForSelector('[role="dialog"]', {
            state: "visible",
            timeout: 5000,
          });

          // Wait for the user list to load (checkboxes appear once API returns)
          await page.waitForSelector('[role="dialog"] button[role="checkbox"]', {
            state: "visible",
            timeout: 10000,
          });
          await waitForContent(page);
        },
      },
      {
        title: "Escolher palestrantes no modal",
        description:
          "Marque os palestrantes desejados na lista e clique em Confirmar.",
        screenshotName: "admin-criar-sem-11-palestrantes-modal",
        action: async (page) => {
          // Select first two speakers by clicking their row checkboxes
          const checkboxes = page.locator(
            '[role="dialog"] button[role="checkbox"]',
          );
          const count = await checkboxes.count();
          if (count > 0) await checkboxes.nth(0).click();
          await page.waitForTimeout(200);
          if (count > 1) await checkboxes.nth(1).click();
          await waitForContent(page);
        },
      },
      {
        title: "Confirmar seleção de palestrantes",
        description:
          'Clique em "Confirmar" para adicionar os palestrantes selecionados.',
        screenshotName: "admin-criar-sem-12-palestrantes-confirmado",
        action: async (page) => {
          // Click confirm button (text includes the count, e.g. "Confirmar (2)")
          await page
            .locator('[role="dialog"] button')
            .filter({ hasText: /Confirmar/ })
            .click();

          // Wait for modal to close
          await page.waitForSelector('[role="dialog"]', {
            state: "hidden",
            timeout: 5000,
          });
          await page.waitForTimeout(500);

          // Scroll to see the selected speakers badges
          await scrollAdmin(page, 999);
          await waitForContent(page);
        },
      },
      {
        title: "Formulário completo",
        description:
          'Revise todas as informações e clique em "Criar Seminário" para publicar.',
        screenshotName: "admin-criar-sem-13-completo",
        action: async (page) => {
          await scrollAdmin(page, 0);
          await waitForContent(page);
        },
      },
    ],
  };
}
