import { runGuide } from "./lib/helpers.js";
import { writeGuideFiles } from "./lib/html-generator.js";
import type { Guide, GuideFactory } from "./lib/types.js";

// --- User guides ---
import criarConta from "./scripts/usuario/01-criar-conta.js";
import loginUsuario from "./scripts/usuario/02-login.js";
import recuperarSenha from "./scripts/usuario/03-recuperar-senha.js";
import navegarSeminarios from "./scripts/usuario/04-navegar-seminarios.js";
import inscreverSeminario from "./scripts/usuario/05-inscrever-seminario.js";
import perfilCertificados from "./scripts/usuario/06-perfil-certificados.js";

// --- Admin guides ---
import loginAdmin from "./scripts/admin/01-login.js";
import dashboardAdmin from "./scripts/admin/02-dashboard.js";
import criarSeminario from "./scripts/admin/03-criar-seminario.js";
import gerenciarUsuarios from "./scripts/admin/04-gerenciar-usuarios.js";
import gerenciarTopicos from "./scripts/admin/05-gerenciar-topicos.js";
import gerenciarLocais from "./scripts/admin/06-gerenciar-locais.js";
import gerenciarWorkshops from "./scripts/admin/07-gerenciar-workshops.js";
import visualizarInscricoes from "./scripts/admin/08-visualizar-inscricoes.js";
import relatorios from "./scripts/admin/09-relatorios.js";
import linkPresenca from "./scripts/admin/10-link-presenca.js";

const guideFactories: GuideFactory[] = [
  // Admin guides
  loginAdmin,
  dashboardAdmin,
  criarSeminario,
  gerenciarUsuarios,
  gerenciarTopicos,
  gerenciarLocais,
  gerenciarWorkshops,
  visualizarInscricoes,
  relatorios,
  linkPresenca,
  // User guides
  criarConta,
  loginUsuario,
  recuperarSenha,
  navegarSeminarios,
  inscreverSeminario,
  perfilCertificados,
];

async function main() {
  console.log("Gerando documentação...\n");

  const guides: Guide[] = guideFactories.map((factory) => factory());

  for (const guide of guides) {
    const label =
      guide.category === "admin" ? "Administrador" : "Usuário";
    console.log(`  [${label}] ${guide.title}`);

    try {
      await runGuide(guide);
      console.log(`    ✓ Concluído (${guide.steps.length} passos)\n`);
    } catch (error) {
      console.error(`    ✗ Erro: ${error}\n`);
    }
  }

  console.log("Gerando páginas HTML...");
  writeGuideFiles(guides);

  console.log("\nDocumentação gerada com sucesso!");
  console.log("Abra guide/dist/index.html no navegador para visualizar.");
}

main().catch((error) => {
  console.error("Erro fatal:", error);
  process.exit(1);
});
