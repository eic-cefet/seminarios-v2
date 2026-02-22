import * as fs from "node:fs";
import * as path from "node:path";
import type { Guide } from "./types.js";

const DIST_DIR = path.resolve(import.meta.dirname, "..", "dist");
const SCREENSHOTS_DIR = path.resolve(import.meta.dirname, "..", "screenshots");

function escapeHtml(text: string): string {
  return text
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function css(): string {
  return `
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      line-height: 1.6;
      color: #1a1a2e;
      background: #f8f9fa;
    }
    .layout { display: flex; min-height: 100vh; }
    .sidebar {
      width: 280px;
      background: #1a1a2e;
      color: #e0e0e0;
      padding: 24px 0;
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      overflow-y: auto;
    }
    .sidebar h1 {
      font-size: 16px;
      padding: 0 20px 16px;
      border-bottom: 1px solid #2d2d44;
      margin-bottom: 16px;
      color: #fff;
    }
    .sidebar h2 {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 12px 20px 8px;
      color: #8888aa;
    }
    .sidebar a {
      display: block;
      padding: 8px 20px;
      color: #c0c0d0;
      text-decoration: none;
      font-size: 14px;
      transition: background 0.15s, color 0.15s;
    }
    .sidebar a:hover { background: #2d2d44; color: #fff; }
    .sidebar a.active { background: #3b82f6; color: #fff; }
    .main { margin-left: 280px; flex: 1; padding: 40px 48px; max-width: 960px; }
    .main h1 { font-size: 28px; margin-bottom: 8px; color: #1a1a2e; }
    .main .description { font-size: 16px; color: #666; margin-bottom: 32px; }
    .step {
      margin-bottom: 40px;
      background: #fff;
      border-radius: 12px;
      border: 1px solid #e5e7eb;
      overflow: hidden;
    }
    .step-header {
      padding: 16px 20px;
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }
    .step-number {
      background: #3b82f6;
      color: #fff;
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      font-weight: 600;
      flex-shrink: 0;
    }
    .step-text h3 { font-size: 16px; font-weight: 600; color: #1a1a2e; }
    .step-text p { font-size: 14px; color: #666; margin-top: 4px; }
    .step-screenshot {
      border-top: 1px solid #e5e7eb;
      padding: 0;
    }
    .step-screenshot img {
      width: 100%;
      display: block;
    }
    .index-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 16px;
    }
    .index-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 20px;
      text-decoration: none;
      color: inherit;
      transition: box-shadow 0.15s, border-color 0.15s;
    }
    .index-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-color: #3b82f6; }
    .index-card h3 { font-size: 16px; margin-bottom: 8px; color: #1a1a2e; }
    .index-card p { font-size: 14px; color: #666; }
    .index-card .badge {
      display: inline-block;
      font-size: 11px;
      padding: 2px 8px;
      border-radius: 12px;
      margin-bottom: 8px;
      font-weight: 500;
    }
    .badge-admin { background: #fee2e2; color: #991b1b; }
    .badge-usuario { background: #dbeafe; color: #1e40af; }
    @media (max-width: 768px) {
      .sidebar { display: none; }
      .main { margin-left: 0; padding: 24px 16px; }
    }
  `;
}

function sidebarHtml(guides: Guide[], activeId?: string): string {
  const adminGuides = guides.filter((g) => g.category === "admin");
  const userGuides = guides.filter((g) => g.category === "usuario");

  const renderLink = (g: Guide) => {
    const active = g.id === activeId ? ' class="active"' : "";
    const href =
      g.category === "admin"
        ? `admin/${g.id}.html`
        : `usuario/${g.id}.html`;
    return `<a href="/${href}"${active}>${escapeHtml(g.title)}</a>`;
  };

  return `
    <nav class="sidebar">
      <h1><a href="/index.html" style="color: inherit; text-decoration: none;">Guia - CEFET-RJ Seminários</a></h1>
      <h2>Administrador</h2>
      ${adminGuides.map(renderLink).join("\n      ")}
      <h2>Usuário</h2>
      ${userGuides.map(renderLink).join("\n      ")}
    </nav>
  `;
}

function pageHtml(
  title: string,
  content: string,
  guides: Guide[],
  activeId?: string,
): string {
  return `<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${escapeHtml(title)} - Guia CEFET-RJ Seminários</title>
  <style>${css()}</style>
</head>
<body>
  <div class="layout">
    ${sidebarHtml(guides, activeId)}
    <main class="main">
      ${content}
    </main>
  </div>
</body>
</html>`;
}

export function generateIndexPage(guides: Guide[]): string {
  const adminGuides = guides.filter((g) => g.category === "admin");
  const userGuides = guides.filter((g) => g.category === "usuario");

  const renderCard = (g: Guide) => {
    const href =
      g.category === "admin"
        ? `admin/${g.id}.html`
        : `usuario/${g.id}.html`;
    const badgeClass =
      g.category === "admin" ? "badge-admin" : "badge-usuario";
    const badgeLabel =
      g.category === "admin" ? "Administrador" : "Usuário";
    return `
      <a class="index-card" href="${href}">
        <span class="badge ${badgeClass}">${badgeLabel}</span>
        <h3>${escapeHtml(g.title)}</h3>
        <p>${escapeHtml(g.description)}</p>
      </a>
    `;
  };

  const content = `
    <h1>Guia de Uso — CEFET-RJ Seminários</h1>
    <p class="description">Documentação interativa com capturas de tela de todos os fluxos do sistema.</p>

    <h2 style="margin-top: 32px; margin-bottom: 4px; font-size: 20px;">Guias do Administrador</h2>
    <div class="index-grid">
      ${adminGuides.map(renderCard).join("\n")}
    </div>

    <h2 style="margin-top: 40px; margin-bottom: 4px; font-size: 20px;">Guias do Usuário</h2>
    <div class="index-grid">
      ${userGuides.map(renderCard).join("\n")}
    </div>
  `;

  return pageHtml("Início", content, guides);
}

export function generateGuidePage(guide: Guide, allGuides: Guide[]): string {
  const stepsHtml = guide.steps
    .map(
      (step, i) => `
      <div class="step">
        <div class="step-header">
          <span class="step-number">${i + 1}</span>
          <div class="step-text">
            <h3>${escapeHtml(step.title)}</h3>
            <p>${escapeHtml(step.description)}</p>
          </div>
        </div>
        <div class="step-screenshot">
          <img src="/screenshots/${step.screenshotName}.png" alt="${escapeHtml(step.title)}" loading="lazy" />
        </div>
      </div>
    `,
    )
    .join("\n");

  const content = `
    <h1>${escapeHtml(guide.title)}</h1>
    <p class="description">${escapeHtml(guide.description)}</p>
    ${stepsHtml}
  `;

  return pageHtml(guide.title, content, allGuides, guide.id);
}

export function writeGuideFiles(guides: Guide[]) {
  // Clean dist
  fs.rmSync(DIST_DIR, { recursive: true, force: true });
  fs.mkdirSync(DIST_DIR, { recursive: true });
  fs.mkdirSync(path.join(DIST_DIR, "admin"), { recursive: true });
  fs.mkdirSync(path.join(DIST_DIR, "usuario"), { recursive: true });
  fs.mkdirSync(path.join(DIST_DIR, "screenshots"), { recursive: true });

  // Copy screenshots
  if (fs.existsSync(SCREENSHOTS_DIR)) {
    const files = fs.readdirSync(SCREENSHOTS_DIR);
    for (const file of files) {
      if (file.endsWith(".png")) {
        fs.copyFileSync(
          path.join(SCREENSHOTS_DIR, file),
          path.join(DIST_DIR, "screenshots", file),
        );
      }
    }
  }

  // Write index
  fs.writeFileSync(
    path.join(DIST_DIR, "index.html"),
    generateIndexPage(guides),
    "utf-8",
  );

  // Write individual guide pages
  for (const guide of guides) {
    const dir = guide.category === "admin" ? "admin" : "usuario";
    fs.writeFileSync(
      path.join(DIST_DIR, dir, `${guide.id}.html`),
      generateGuidePage(guide, guides),
      "utf-8",
    );
  }

  console.log(`\n  Gerados ${guides.length} guias em ${DIST_DIR}/`);
}
