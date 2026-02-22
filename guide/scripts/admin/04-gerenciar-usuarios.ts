import type { Guide } from "../../lib/types.js";
import {
    loginAsAdmin,
    waitForContent,
    navigateAdmin,
} from "../../lib/helpers.js";

export default function gerenciarUsuarios(): Guide {
    return {
        id: "admin-gerenciar-usuarios",
        title: "Como gerenciar usuários",
        description:
            "Como visualizar, pesquisar e editar usuários no painel administrativo.",
        category: "admin",
        steps: [
            {
                title: "Acessar a lista de usuários",
                description:
                    'Faça login como administrador e clique em "Usuários" no menu lateral.',
                screenshotName: "admin-usuarios-01-lista",
                action: async (page) => {
                    await loginAsAdmin(page);
                    await navigateAdmin(page, "/users");
                    await waitForContent(page);
                },
            },
            {
                title: "Pesquisar usuários",
                description:
                    "Use a barra de pesquisa para encontrar usuários por nome ou e-mail.",
                screenshotName: "admin-usuarios-02-pesquisa",
                action: async (page) => {
                    await page.fill("#search", "Estudante");
                    await waitForContent(page);
                },
            },
            {
                title: "Filtrar por papel",
                description:
                    "Use o filtro de função (Admin, Professor, Usuário) para encontrar tipos específicos de usuários.",
                screenshotName: "admin-usuarios-03-filtro",
                action: async (page) => {
                    // Clear search first
                    await page.fill("#search", "");
                    await page.waitForTimeout(500);

                    // Click the role filter Radix Select trigger
                    await page.click("#role-filter");
                    await page.waitForSelector('[role="option"]', {
                        state: "visible",
                        timeout: 5000,
                    });
                    // Select "Admin"
                    await page
                        .locator('[role="option"]')
                        .filter({ hasText: "Admin" })
                        .click();
                    await waitForContent(page);
                },
            },
            {
                title: "Visualizar lista filtrada",
                description:
                    "A tabela mostra nome, e-mail, papel e ações disponíveis para cada usuário.",
                screenshotName: "admin-usuarios-04-tabela",
                action: async (page) => {
                    // Reset the filter back to "Todas" for the next step
                    await page.click("#role-filter");
                    await page.waitForSelector('[role="option"]', {
                        state: "visible",
                        timeout: 5000,
                    });
                    await page
                        .locator('[role="option"]')
                        .filter({ hasText: "Todas" })
                        .click();
                    await waitForContent(page);
                },
            },
            {
                title: "Criar novo usuário",
                description:
                    'Clique em "Novo Usuario" para abrir o formulário de criação.',
                screenshotName: "admin-usuarios-05-botao-novo",
                action: async (page) => {
                    await page.click('button:has-text("Novo Usuario")');
                    await page.waitForSelector('[role="dialog"]', {
                        state: "visible",
                        timeout: 5000,
                    });
                    await waitForContent(page);
                },
            },
            {
                title: "Preencher os dados do usuário",
                description: "Informe nome, e-mail e senha do novo usuário.",
                screenshotName: "admin-usuarios-06-form-preenchido",
                action: async (page) => {
                    await page.fill('[role="dialog"] #name', "João da Silva");
                    await page.fill(
                        '[role="dialog"] #email',
                        "joao.silva@exemplo.com",
                    );
                    await page.fill('[role="dialog"] #password', "senha12345");
                    await waitForContent(page);
                },
            },
        ],
    };
}
