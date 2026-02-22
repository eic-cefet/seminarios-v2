import type { Guide } from "../../lib/types.js";
import { waitForContent } from "../../lib/helpers.js";

export default function loginUsuario(): Guide {
  return {
    id: "login",
    title: "Como fazer login",
    description:
      "Como acessar sua conta no sistema usando e-mail e senha ou login social.",
    category: "usuario",
    steps: [
      {
        title: "Acessar a página de login",
        description:
          'Clique em "Entrar" no menu superior ou acesse diretamente a página de login.',
        screenshotName: "usuario-login-01-pagina",
        action: async (page) => {
          await page.goto("/login");
          await waitForContent(page, "#email");
        },
      },
      {
        title: "Preencher as credenciais",
        description:
          "Digite seu e-mail e senha nos campos correspondentes.",
        screenshotName: "usuario-login-02-preenchido",
        action: async (page) => {
          await page.fill("#email", "student@cefet-rj.br");
          await page.fill("#password", "password");
        },
      },
      {
        title: "Entrar no sistema",
        description:
          'Clique no botão "Entrar" para acessar sua conta. Você será redirecionado para a página inicial.',
        screenshotName: "usuario-login-03-logado",
        action: async (page) => {
          await page.click('button[type="submit"]');
          await page.waitForLoadState("networkidle");
          await page.waitForURL((url) => !url.pathname.endsWith("/login"), {
            timeout: 10000,
          });
          await waitForContent(page);
        },
      },
    ],
  };
}
