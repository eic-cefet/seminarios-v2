import type { Guide } from "../../lib/types.js";
import {
    loginAsAdmin,
    waitForContent,
    navigateAdmin,
} from "../../lib/helpers.js";

export default function gerenciarTopicos(): Guide {
    return {
        id: "admin-gerenciar-topicos",
        title: "Como gerenciar tópicos",
        description:
            "Como criar, editar e excluir tópicos (assuntos) dos seminários.",
        category: "admin",
        steps: [
            {
                title: "Acessar a lista de tópicos",
                description:
                    "Faça login como administrador e acesse Seminários → Tópicos no menu lateral.",
                screenshotName: "admin-topicos-01-lista",
                action: async (page) => {
                    await loginAsAdmin(page);
                    await navigateAdmin(page, "/subjects");
                    await waitForContent(page);
                },
            },
            {
                title: "Visualizar tópicos existentes",
                description:
                    "A tabela lista todos os tópicos com o número de seminários associados a cada um.",
                screenshotName: "admin-topicos-02-listagem",
                action: async (page) => {
                    await waitForContent(page);
                },
            },
            {
                title: 'Clicar em "Novo Tópico"',
                description:
                    'Clique no botão "Novo Tópico" para abrir o formulário de criação.',
                screenshotName: "admin-topicos-03-botao-novo",
                action: async (page) => {
                    await page.click('button:has-text("Novo Tópico")');
                    await page.waitForSelector('[role="dialog"]', {
                        state: "visible",
                        timeout: 5000,
                    });
                    await waitForContent(page);
                },
            },
            {
                title: "Preencher o nome do tópico",
                description:
                    'Digite o nome do novo tópico e clique em "Salvar".',
                screenshotName: "admin-topicos-04-form-preenchido",
                action: async (page) => {
                    await page.fill(
                        '[role="dialog"] #name',
                        "Computação Quântica",
                    );
                    await waitForContent(page);
                },
            },
        ],
    };
}
