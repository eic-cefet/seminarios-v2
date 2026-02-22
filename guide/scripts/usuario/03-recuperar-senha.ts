import type { Guide } from "../../lib/types.js";
import { waitForContent } from "../../lib/helpers.js";

export default function recuperarSenha(): Guide {
  return {
    id: "recuperar-senha",
    title: "Como recuperar a senha",
    description:
      "Como solicitar a redefinição de senha caso você tenha esquecido.",
    category: "usuario",
    steps: [
      {
        title: "Acessar a recuperação de senha",
        description:
          'Na página de login, clique em "Esqueceu a senha?" abaixo do campo de senha.',
        screenshotName: "usuario-recuperar-01-link",
        action: async (page) => {
          await page.goto("/login");
          await waitForContent(page, 'a[href="/recuperar-senha"]');
        },
      },
      {
        title: "Página de recuperação de senha",
        description:
          "Você será redirecionado para a página de recuperação de senha.",
        screenshotName: "usuario-recuperar-02-pagina",
        action: async (page) => {
          await page.goto("/recuperar-senha");
          await waitForContent(page);
        },
      },
      {
        title: "Informar o e-mail",
        description:
          "Digite o e-mail cadastrado na sua conta e clique em enviar.",
        screenshotName: "usuario-recuperar-03-email",
        action: async (page) => {
          await page.fill('input[type="email"]', "student@cefet-rj.br");
          await waitForContent(page);
        },
      },
    ],
  };
}
