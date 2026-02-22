import type { Guide } from "../../lib/types.js";
import { loginAsAdmin, waitForContent } from "../../lib/helpers.js";

export default function loginAdmin(): Guide {
    return {
        id: "admin-login",
        title: "Como fazer login como administrador",
        description:
            "Como acessar o painel administrativo do sistema de seminários.",
        category: "admin",
        steps: [
            {
                title: "Acessar a página de login",
                description: "Acesse a página de login do sistema.",
                screenshotName: "admin-login-01-pagina",
                action: async (page) => {
                    await page.goto("/login");
                    await waitForContent(page, "#email");
                },
            },
            {
                title: "Preencher as credenciais de administrador",
                description:
                    "Digite o e-mail e senha do administrador nos campos correspondentes.",
                screenshotName: "admin-login-02-credenciais",
                action: async (page) => {
                    await page.fill("#email", "admin@cefet-rj.br");
                    await page.fill("#password", "password");
                },
            },
            {
                title: "Entrar e acessar o painel",
                description:
                    'Clique em "Entrar" e depois acesse o painel administrativo pelo ícone de escudo no menu.',
                screenshotName: "admin-login-03-logado",
                action: async (page) => {
                    await page.click('button[type="submit"]');
                    await page.waitForLoadState("networkidle");
                    await page.waitForURL(
                        (url) => !url.pathname.endsWith("/login"),
                        {
                            timeout: 10000,
                        },
                    );
                    await waitForContent(page);
                },
            },
            {
                title: "Painel administrativo",
                description:
                    "Ao acessar /admin, você verá o dashboard com as principais informações do sistema.",
                screenshotName: "admin-login-04-dashboard",
                action: async (page) => {
                    await page.goto("/admin");
                    await page.waitForLoadState("networkidle");
                    await waitForContent(page);
                },
            },
        ],
    };
}
