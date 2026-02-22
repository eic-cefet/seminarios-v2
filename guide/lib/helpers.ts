import { type Page, chromium } from "playwright";
import * as path from "node:path";
import * as fs from "node:fs";
import type { Guide, GuideStep } from "./types.js";

const BASE_URL = process.env.BASE_URL || "http://localhost:8000";
const SCREENSHOTS_DIR = path.resolve(import.meta.dirname, "..", "screenshots");
const VIEWPORT = { width: 1280, height: 800 };

/**
 * Wait until the page is truly settled:
 * 1. Wait for network idle (initial HTML/JS loaded)
 * 2. Wait a beat for React to mount and start API calls
 * 3. Wait for network idle again (API responses received)
 * 4. Wait for all loading indicators to disappear (skeletons, spinners, "Carregando...")
 */
async function waitUntilSettled(page: Page) {
  await page.waitForLoadState("networkidle");
  // Give React time to mount and fire TanStack Query requests
  await page.waitForTimeout(500);
  // Wait for those API requests to complete
  await page.waitForLoadState("networkidle");
  // Now poll until all loading indicators are gone
  try {
    await page.waitForFunction(
      () => {
        if (document.querySelectorAll(".animate-pulse").length > 0) return false;
        if (document.querySelectorAll(".animate-spin").length > 0) return false;
        const body = document.body.textContent || "";
        if (body.includes("Carregando...")) return false;
        return true;
      },
      { timeout: 10000 },
    );
  } catch {
    // Continue if timeout — some pages may not have indicators
  }
  // Final buffer for rendering
  await page.waitForTimeout(300);
}

export async function createBrowser() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: VIEWPORT,
    locale: "pt-BR",
    baseURL: BASE_URL,
  });
  return { browser, context };
}

export async function loginAs(
  page: Page,
  email: string,
  password: string = "password",
) {
  await page.goto("/login");
  await page.waitForSelector("#email", { state: "visible", timeout: 10000 });
  await page.fill("#email", email);
  await page.fill("#password", password);
  // Click and wait for navigation in parallel — don't wait for networkidle
  // before the URL check, since the SPA redirect + API calls can stall it
  await Promise.all([
    page.waitForURL((url) => !url.pathname.endsWith("/login"), {
      timeout: 30000,
    }),
    page.click('button[type="submit"]'),
  ]);
  await waitUntilSettled(page);
}

export async function loginAsAdmin(page: Page) {
  await loginAs(page, "admin@cefet-rj.br");
}

export async function loginAsTeacher(page: Page) {
  await loginAs(page, "teacher@cefet-rj.br");
}

export async function loginAsStudent(page: Page) {
  await loginAs(page, "student@cefet-rj.br");
}

export async function waitForContent(page: Page, selector?: string) {
  await waitUntilSettled(page);
  if (selector) {
    await page.waitForSelector(selector, { state: "visible", timeout: 10000 });
  }
}

export async function captureScreenshot(page: Page, name: string) {
  fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
  const filePath = path.join(SCREENSHOTS_DIR, `${name}.png`);
  await page.screenshot({ path: filePath, fullPage: false });
  return filePath;
}

export async function runGuide(guide: Guide): Promise<string[]> {
  const { browser, context } = await createBrowser();
  const page = await context.newPage();
  const screenshots: string[] = [];

  try {
    for (const step of guide.steps) {
      console.log(`    [${guide.id}] ${step.title}`);
      await step.action(page);
      const screenshotPath = await captureScreenshot(page, step.screenshotName);
      screenshots.push(screenshotPath);
    }
  } finally {
    await browser.close();
  }

  return screenshots;
}

export async function navigateAdmin(page: Page, path: string) {
  await page.goto(`/admin${path}`);
  await waitUntilSettled(page);
}

/**
 * Scroll within the admin panel's main content area.
 * The admin layout uses `h-screen overflow-hidden` on the body,
 * so `window.scrollTo()` doesn't work — the `<main>` element is the scroll container.
 */
export async function scrollAdmin(page: Page, y: number) {
  await page.evaluate((scrollY) => {
    const main = document.querySelector("main");
    if (main) {
      main.scrollTo(0, scrollY);
    } else {
      window.scrollTo(0, scrollY);
    }
  }, y);
}

export async function clickRadixSelect(
  page: Page,
  triggerSelector: string,
  optionIndex: number = 0,
) {
  await page.click(triggerSelector);
  await page.waitForSelector('[role="option"]', {
    state: "visible",
    timeout: 5000,
  });
  const options = await page.locator('[role="option"]').all();
  if (options.length > optionIndex) {
    await options[optionIndex].click();
  }
  await page.waitForTimeout(300);
}
