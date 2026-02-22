import type { Guide } from "../../lib/types.js";
import {
    loginAsAdmin,
    waitForContent,
    navigateAdmin,
    scrollAdmin,
} from "../../lib/helpers.js";

export default function dashboardAdmin(): Guide {
    return {
        id: "admin-dashboard",
        title: "Visão geral do painel administrativo",
        description:
            "Conheça o dashboard do administrador e as informações disponíveis.",
        category: "admin",
        steps: [
            {
                title: "Acessar o painel",
                description: "Faça login como administrador e acesse o painel.",
                screenshotName: "admin-dashboard-01-login",
                action: async (page) => {
                    await loginAsAdmin(page);
                    await navigateAdmin(page, "/");
                    await waitForContent(page);
                },
            },
            {
                title: "Estatísticas gerais",
                description:
                    "No topo do dashboard, veja os cards com o total de usuários, seminários, inscrições e tópicos.",
                screenshotName: "admin-dashboard-02-stats",
                action: async (page) => {
                    await waitForContent(page);
                },
            },
            {
                title: "Próximos seminários e inscrições",
                description:
                    "Role a página para ver os próximos seminários agendados e as inscrições mais recentes.",
                screenshotName: "admin-dashboard-03-proximos",
                action: async (page) => {
                    await scrollAdmin(page, 400);
                    await waitForContent(page);
                },
            },
            {
                title: "Avaliações recentes",
                description:
                    "Continue rolando para ver as últimas avaliações dos participantes.",
                screenshotName: "admin-dashboard-04-avaliacoes",
                action: async (page) => {
                    await scrollAdmin(page, 800);
                    await waitForContent(page);
                },
            },
        ],
    };
}
