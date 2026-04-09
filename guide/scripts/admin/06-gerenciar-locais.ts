import type { Guide } from "../../lib/types.js";
import {
    loginAsAdmin,
    waitForContent,
    navigateAdmin,
} from "../../lib/helpers.js";

export default function gerenciarLocais(): Guide {
    return {
        id: "admin-gerenciar-locais",
        title: "Como gerenciar locais",
        description:
            "Como cadastrar e gerenciar os locais onde os seminários são realizados.",
        category: "admin",
        steps: [
            {
                title: "Acessar a lista de locais",
                description:
                    "Faça login como administrador e acesse Seminários → Locais no menu lateral.",
                screenshotName: "admin-locais-01-lista",
                action: async (page) => {
                    await loginAsAdmin(page);
                    await navigateAdmin(page, "/locations");
                    await waitForContent(page);
                },
            },
            {
                title: "Visualizar locais cadastrados",
                description:
                    "A tabela lista todos os locais com nome, capacidade e quantidade de seminários associados.",
                screenshotName: "admin-locais-02-listagem",
                action: async (page) => {
                    await waitForContent(page);
                },
            },
            {
                title: 'Clicar em "Novo Local"',
                description:
                    'Clique no botão "Novo Local" para abrir o formulário de criação.',
                screenshotName: "admin-locais-03-botao-novo",
                action: async (page) => {
                    await page.click('button:has-text("Novo Local")');
                    await page.waitForSelector('[role="dialog"]', {
                        state: "visible",
                        timeout: 5000,
                    });
                    await waitForContent(page);
                },
            },
            {
                title: "Preencher os dados do local",
                description:
                    "Informe o nome do local e a capacidade máxima de participantes.",
                screenshotName: "admin-locais-04-form-preenchido",
                action: async (page) => {
                    await page.fill(
                        '[role="dialog"] #name',
                        "Auditório Principal",
                    );
                    await page.fill('[role="dialog"] #max_vacancies', "120");
                    await waitForContent(page);
                },
            },
        ],
    };
}
