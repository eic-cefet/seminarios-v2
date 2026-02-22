import type { Guide } from "../../lib/types.js";
import { waitForContent } from "../../lib/helpers.js";

export default function criarConta(): Guide {
    return {
        id: "criar-conta",
        title: "Como criar uma conta",
        description:
            "Passo a passo para criar uma conta no sistema de seminários do CEFET-RJ.",
        category: "usuario",
        steps: [
            {
                title: "Acessar a página inicial",
                description:
                    'Acesse o sistema e clique em "Entrar" no canto superior direito.',
                screenshotName: "usuario-criar-conta-01-home",
                action: async (page) => {
                    await page.goto("/");
                    await waitForContent(page);
                },
            },
            {
                title: "Ir para a página de cadastro",
                description:
                    'Na página de login, clique em "Criar conta" na parte inferior do formulário.',
                screenshotName: "usuario-criar-conta-02-login",
                action: async (page) => {
                    await page.goto("/login");
                    await waitForContent(page, 'a[href="/cadastro"]');
                },
            },
            {
                title: "Preencher os dados pessoais",
                description: "Informe seu nome completo e e-mail.",
                screenshotName: "usuario-criar-conta-03-form-nome",
                action: async (page) => {
                    await page.goto("/cadastro");
                    await waitForContent(page, "#name");
                    await page.fill("#name", "Maria Silva");
                    await page.fill("#email", "maria.silva@exemplo.com");
                },
            },
            {
                title: "Selecionar informações acadêmicas",
                description:
                    "Escolha sua situação no curso (cursando ou formado), seu vínculo e o curso que frequenta.",
                screenshotName: "usuario-criar-conta-04-form-curso",
                action: async (page) => {
                    await page.selectOption("#courseSituation", "studying");
                    await page.selectOption("#courseRole", "Aluno");
                    // Select first available course
                    const courseOptions = await page
                        .locator("#courseId option")
                        .all();
                    if (courseOptions.length > 1) {
                        const value =
                            await courseOptions[1].getAttribute("value");
                        if (value) await page.selectOption("#courseId", value);
                    }
                    await waitForContent(page);
                },
            },
            {
                title: "Definir a senha",
                description:
                    "Crie uma senha com no mínimo 8 caracteres e confirme.",
                screenshotName: "usuario-criar-conta-05-form-senha",
                action: async (page) => {
                    await page.fill("#password", "senha12345");
                    await page.fill("#passwordConfirmation", "senha12345");
                    await waitForContent(page);
                },
            },
            {
                title: "Formulário preenchido",
                description:
                    'Revise todos os dados e clique em "Criar conta" para finalizar o cadastro.',
                screenshotName: "usuario-criar-conta-06-form-completo",
                action: async (page) => {
                    await page.evaluate(() => window.scrollTo(0, 999));
                    await waitForContent(page);
                },
            },
        ],
    };
}
