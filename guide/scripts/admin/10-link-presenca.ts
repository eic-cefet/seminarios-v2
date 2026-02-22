import type { Guide } from "../../lib/types.js";
import {
  loginAsAdmin,
  waitForContent,
  navigateAdmin,
} from "../../lib/helpers.js";

export default function linkPresenca(): Guide {
  return {
    id: "admin-link-presenca",
    title: "Como obter o link de presença com QR Code",
    description:
      "Como gerar e compartilhar o link de presença e QR Code de um seminário para registro de participação.",
    category: "admin",
    steps: [
      {
        title: "Acessar a lista de seminários",
        description:
          "Faça login como administrador e acesse a lista de seminários.",
        screenshotName: "admin-presenca-01-lista",
        action: async (page) => {
          await loginAsAdmin(page);
          await navigateAdmin(page, "/seminars");
          await waitForContent(page);
        },
      },
      {
        title: "Localizar o botão de QR Code",
        description:
          "Na coluna de ações de cada seminário, clique no ícone de QR Code para abrir o modal de presença.",
        screenshotName: "admin-presenca-02-botao-qr",
        action: async (page) => {
          await waitForContent(page);
        },
      },
      {
        title: "Abrir o modal de presença",
        description:
          "Clique no ícone de QR Code do seminário desejado.",
        screenshotName: "admin-presenca-03-modal",
        action: async (page) => {
          // Click the first QR code button in the table
          const qrButton = page.locator(
            'button[title="Link de Presença (QR Code)"]',
          ).first();
          await qrButton.click();

          // Wait for the modal to open
          await page.waitForSelector('[role="dialog"]', {
            state: "visible",
            timeout: 5000,
          });

          // Wait for the presence link data to load
          await page.waitForLoadState("networkidle");
          await waitForContent(page);
        },
      },
      {
        title: "Visualizar o link e QR Code",
        description:
          "O modal exibe o status do link, a URL para compartilhar e o QR Code que os participantes podem escanear.",
        screenshotName: "admin-presenca-04-qrcode",
        action: async (page) => {
          // Scroll inside the modal to show the QR code
          const dialog = page.locator('[role="dialog"]');
          await dialog.evaluate((el) => {
            const scrollable = el.querySelector(".overflow-y-auto");
            if (scrollable) scrollable.scrollTo(0, 999);
          });
          await waitForContent(page);
        },
      },
      {
        title: "Copiar o link",
        description:
          'Use o botão de copiar ao lado do link para copiá-lo. Compartilhe com os participantes ou projete o QR Code na sala.',
        screenshotName: "admin-presenca-05-copiar",
        action: async (page) => {
          // Scroll back to the top of the modal to show the link section
          const dialog = page.locator('[role="dialog"]');
          await dialog.evaluate((el) => {
            const scrollable = el.querySelector(".overflow-y-auto");
            if (scrollable) scrollable.scrollTo(0, 0);
          });
          await waitForContent(page);
        },
      },
    ],
  };
}
