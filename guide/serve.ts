import * as http from "node:http";
import * as fs from "node:fs";
import * as path from "node:path";

const DIST_DIR = path.resolve(import.meta.dirname, "dist");
const PORT = Number(process.env.PORT) || 3000;

const MIME_TYPES: Record<string, string> = {
  ".html": "text/html; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".js": "application/javascript; charset=utf-8",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".svg": "image/svg+xml",
  ".ico": "image/x-icon",
};

const server = http.createServer((req, res) => {
  const url = new URL(req.url || "/", `http://localhost:${PORT}`);
  let filePath = path.join(DIST_DIR, url.pathname);

  // Serve index.html for directory requests
  if (filePath.endsWith("/")) {
    filePath = path.join(filePath, "index.html");
  }

  // If no extension, try .html
  if (!path.extname(filePath) && !fs.existsSync(filePath)) {
    filePath += ".html";
  }

  if (!fs.existsSync(filePath)) {
    res.writeHead(404, { "Content-Type": "text/html; charset=utf-8" });
    res.end("<h1>404 — Página não encontrada</h1>");
    return;
  }

  const ext = path.extname(filePath);
  const contentType = MIME_TYPES[ext] || "application/octet-stream";

  const content = fs.readFileSync(filePath);
  res.writeHead(200, { "Content-Type": contentType });
  res.end(content);
});

server.listen(PORT, () => {
  console.log(`Guia disponível em http://localhost:${PORT}`);
});
