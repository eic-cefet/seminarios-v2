# System SPA Accessibility Pass Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bring the public-facing System SPA to WCAG 2.1 AA for the most-used flows (home, browsing, auth, registration, profile).

**Architecture:** Static guardrail via `eslint-plugin-jsx-a11y`, runtime guardrail via `jest-axe` in targeted component tests, plus direct markup fixes (semantic landmarks, skip link, form `aria-*` wiring, icon labels, focus management, loading announcements, contrast). Scope is strictly `resources/js/system/` and the shared primitives it uses; the Admin SPA is out of scope.

**Tech Stack:** React 19, Radix UI, Tailwind v4, Vitest + React Testing Library, `eslint-plugin-jsx-a11y`, `jest-axe`.

---

## File Structure

**New files**
- `resources/js/system/components/SkipLink.tsx` — visible-on-focus skip-to-main-content link.
- `resources/js/shared/components/FormField.tsx` — accessible label + input + error wrapper (auto-wires `id`, `aria-invalid`, `aria-describedby`).
- `resources/js/shared/components/LoadingRegion.tsx` — `role="status"` wrapper for skeleton/spinner blocks.
- `resources/js/system/components/Layout.test.tsx` — axe + landmark tests for the shared layout shell.
- `resources/js/shared/components/FormField.test.tsx` — unit tests for the form field.
- `resources/js/shared/components/LoadingRegion.test.tsx` — unit tests for the loading wrapper.

**Modified files**
- `eslint.config.js` or equivalent — add `jsx-a11y` plugin + recommended rules.
- `package.json` — new dev deps: `eslint-plugin-jsx-a11y`, `jest-axe`, `@types/jest-axe`.
- `vitest.config.ts` (or `vitest.setup.ts`) — extend `expect` with `toHaveNoViolations`.
- `resources/js/system/components/Layout.tsx` — wrap content in `<main id="main-content">`, render `<SkipLink>`.
- `resources/js/system/components/Navbar.tsx` — `<nav aria-label="...">` + `aria-expanded`/`aria-controls` on mobile toggle.
- `resources/js/system/components/Footer.tsx` — `<footer role="contentinfo">` + fix low-contrast text.
- `resources/js/system/pages/Login.tsx` — migrate inputs to `FormField`, password visibility toggle labelled.
- `resources/js/system/pages/Register.tsx` — migrate to `FormField`, link confirm-password mismatch error.
- `resources/js/system/components/profile/ProfileInfoSection.tsx` — migrate to `FormField`.
- `resources/js/system/components/profile/PasswordSection.tsx` — migrate to `FormField`.
- `resources/js/system/components/LoginModal.tsx` — initial focus to email input, `FormField` usage.
- `resources/js/system/components/CalendarMenu.tsx` — `aria-label` on each option, external link `aria-describedby` hint.
- `resources/js/system/pages/Home.tsx`, `Presentations.tsx`, `Subjects.tsx`, `Workshops.tsx`, `SeminarDetails.tsx`, `WorkshopDetails.tsx` — wrap loading skeletons in `<LoadingRegion>`; fix `text-gray-400` contrast where it carries meaning.

Each file has one focused responsibility. The two shared components (`FormField`, `LoadingRegion`) exist to prevent the same bug from reappearing across pages.

---

## Task 1: Tooling — ESLint jsx-a11y + jest-axe

**Files:**
- Modify: `package.json`
- Modify: `eslint.config.js` (or `.eslintrc.*` — detect current)
- Modify: `vitest.setup.ts` (or create if absent; reference from `vitest.config.ts`)

- [ ] **Step 1: Detect existing ESLint config**

Run: `ls eslint.config.* .eslintrc.* 2>/dev/null; cat vitest.config.ts`
Note which file holds ESLint rules and whether `setupFiles` is configured in Vitest. Use those exact paths in the following steps.

- [ ] **Step 2: Install dependencies**

```bash
pnpm add -D eslint-plugin-jsx-a11y jest-axe @types/jest-axe
```

- [ ] **Step 3: Wire jsx-a11y into ESLint config**

If `eslint.config.js` (flat config) exists, add:

```js
import jsxA11y from "eslint-plugin-jsx-a11y";

// inside the exported array, for files matching .tsx/.jsx:
{
    files: ["resources/js/**/*.{ts,tsx,jsx}"],
    plugins: { "jsx-a11y": jsxA11y },
    rules: { ...jsxA11y.configs.recommended.rules },
}
```

If legacy `.eslintrc.*`, add `"plugin:jsx-a11y/recommended"` to `extends` and `"jsx-a11y"` to `plugins`.

- [ ] **Step 4: Add jest-axe matcher to Vitest setup**

Create or edit `vitest.setup.ts`:

```ts
import "@testing-library/jest-dom/vitest";
import { expect } from "vitest";
import { toHaveNoViolations } from "jest-axe";

expect.extend(toHaveNoViolations);
```

In `vitest.config.ts` under `test`, ensure `setupFiles: ["./vitest.setup.ts"]` is present.

- [ ] **Step 5: Run lint to capture current baseline**

Run: `pnpm exec eslint "resources/js/system/**/*.{ts,tsx}" --no-error-on-unmatched-pattern | tee /tmp/a11y-baseline.txt`
Expected: a list of jsx-a11y violations. This is the baseline subsequent tasks will drive toward zero.

- [ ] **Step 6: Run the frontend test suite to confirm nothing broke**

Run: `pnpm exec vitest run`
Expected: all existing tests still pass.

- [ ] **Step 7: Commit**

```bash
git add package.json pnpm-lock.yaml eslint.config.js vitest.setup.ts vitest.config.ts
git commit -m "chore: add jsx-a11y lint plugin and jest-axe matcher"
```

---

## Task 2: Landmark shell — SkipLink + `<main id>` + `<nav aria-label>`

**Files:**
- Create: `resources/js/system/components/SkipLink.tsx`
- Create: `resources/js/system/components/Layout.test.tsx`
- Modify: `resources/js/system/components/Layout.tsx`
- Modify: `resources/js/system/components/Navbar.tsx`
- Modify: `resources/js/system/components/Footer.tsx`

- [ ] **Step 1: Write the failing layout test**

Create `resources/js/system/components/Layout.test.tsx`:

```tsx
import { render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import { axe } from "jest-axe";
import { describe, it, expect } from "vitest";
import { Layout } from "./Layout";

function renderLayout() {
    return render(
        <MemoryRouter>
            <Layout><p>child content</p></Layout>
        </MemoryRouter>,
    );
}

describe("Layout", () => {
    it("exposes a skip link targeting #main-content", () => {
        renderLayout();
        const skip = screen.getByRole("link", { name: /pular para o conteúdo/i });
        expect(skip).toHaveAttribute("href", "#main-content");
    });

    it("renders a labelled <main> landmark", () => {
        renderLayout();
        expect(screen.getByRole("main")).toHaveAttribute("id", "main-content");
    });

    it("has no detectable axe violations", async () => {
        const { container } = renderLayout();
        expect(await axe(container)).toHaveNoViolations();
    });
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `pnpm exec vitest run resources/js/system/components/Layout.test.tsx`
Expected: FAIL — skip link not found, `<main>` missing `id`.

- [ ] **Step 3: Create `SkipLink`**

Create `resources/js/system/components/SkipLink.tsx`:

```tsx
export function SkipLink() {
    return (
        <a
            href="#main-content"
            className="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-primary-600 focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-primary-300"
        >
            Pular para o conteúdo
        </a>
    );
}
```

- [ ] **Step 4: Update `Layout.tsx` to render the skip link and add `id` to `<main>`**

Edit `resources/js/system/components/Layout.tsx`:

```tsx
import { ReactNode } from "react";
import { Favicon } from "@shared/components/Favicon";
import { Navbar } from "./Navbar";
import { Footer } from "./Footer";
import { SkipLink } from "./SkipLink";

interface LayoutProps {
    children: ReactNode;
}

export function Layout({ children }: LayoutProps) {
    return (
        <div className="min-h-screen flex flex-col bg-gray-50">
            <Favicon />
            <SkipLink />
            <Navbar />
            <main id="main-content" tabIndex={-1} className="flex-1">
                {children}
            </main>
            <Footer />
        </div>
    );
}
```

- [ ] **Step 5: Label the primary `<nav>` and wire mobile toggle ARIA**

Edit `resources/js/system/components/Navbar.tsx`. Change `<nav className="bg-white border-b border-gray-200">` to `<nav aria-label="Navegação principal" className="bg-white border-b border-gray-200">`. Update the mobile menu button (around line 82-98):

```tsx
<button
    type="button"
    aria-expanded={mobileMenuOpen}
    aria-controls="mobile-nav"
    onClick={() => {
        if (!mobileMenuOpen) {
            analytics.event("navbar_menu_open");
        }
        setMobileMenuOpen(!mobileMenuOpen);
    }}
    className="inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
>
    <span className="sr-only">{mobileMenuOpen ? "Fechar menu" : "Abrir menu"}</span>
    {mobileMenuOpen ? <X className="h-6 w-6" aria-hidden="true" /> : <Menu className="h-6 w-6" aria-hidden="true" />}
</button>
```

And add `id="mobile-nav"` to the collapsing `<div className={cn("sm:hidden", ...)}>` below.

Note: `text-gray-400` on the trigger was flagged for contrast — the replacement uses `text-gray-500`.

- [ ] **Step 6: Add `role="contentinfo"` and fix Footer contrast**

Open `resources/js/system/components/Footer.tsx` and verify it is a `<footer>` element. It is by default a landmark, but explicitly add `role="contentinfo"` only if the file uses a `<div>`. Replace any `text-gray-400` that contains real text (not decoration) with `text-gray-600`.

- [ ] **Step 7: Run layout test**

Run: `pnpm exec vitest run resources/js/system/components/Layout.test.tsx`
Expected: PASS, including axe.

- [ ] **Step 8: Run lint on changed files**

Run: `pnpm exec eslint resources/js/system/components/Layout.tsx resources/js/system/components/Navbar.tsx resources/js/system/components/Footer.tsx resources/js/system/components/SkipLink.tsx`
Expected: no errors.

- [ ] **Step 9: Commit**

```bash
git add resources/js/system/components/Layout.tsx resources/js/system/components/Layout.test.tsx resources/js/system/components/SkipLink.tsx resources/js/system/components/Navbar.tsx resources/js/system/components/Footer.tsx
git commit -m "feat(a11y): add skip link, main landmark id, nav labels"
```

---

## Task 3: `FormField` shared component

**Files:**
- Create: `resources/js/shared/components/FormField.tsx`
- Create: `resources/js/shared/components/FormField.test.tsx`

- [ ] **Step 1: Write the failing test**

Create `resources/js/shared/components/FormField.test.tsx`:

```tsx
import { render, screen } from "@testing-library/react";
import { axe } from "jest-axe";
import { describe, it, expect } from "vitest";
import { FormField } from "./FormField";

describe("FormField", () => {
    it("associates label with input via htmlFor/id", () => {
        render(<FormField id="email" label="E-mail" type="email" />);
        const input = screen.getByLabelText("E-mail");
        expect(input).toHaveAttribute("id", "email");
    });

    it("marks required fields with aria-required", () => {
        render(<FormField id="email" label="E-mail" required />);
        expect(screen.getByLabelText(/e-mail/i)).toHaveAttribute("aria-required", "true");
    });

    it("exposes aria-invalid and links error via aria-describedby", () => {
        render(<FormField id="email" label="E-mail" error="E-mail inválido" />);
        const input = screen.getByLabelText("E-mail");
        expect(input).toHaveAttribute("aria-invalid", "true");
        const errorId = input.getAttribute("aria-describedby");
        expect(errorId).toBeTruthy();
        expect(screen.getByText("E-mail inválido")).toHaveAttribute("id", errorId!);
        expect(screen.getByText("E-mail inválido")).toHaveAttribute("role", "alert");
    });

    it("links help text via aria-describedby", () => {
        render(<FormField id="pw" label="Senha" hint="Mínimo de 8 caracteres" />);
        const input = screen.getByLabelText("Senha");
        const describedBy = input.getAttribute("aria-describedby");
        expect(describedBy).toContain("pw-hint");
    });

    it("has no axe violations when valid", async () => {
        const { container } = render(<FormField id="email" label="E-mail" />);
        expect(await axe(container)).toHaveNoViolations();
    });
});
```

- [ ] **Step 2: Run it**

Run: `pnpm exec vitest run resources/js/shared/components/FormField.test.tsx`
Expected: FAIL — module does not exist.

- [ ] **Step 3: Implement `FormField`**

Create `resources/js/shared/components/FormField.tsx`:

```tsx
import { forwardRef, InputHTMLAttributes, ReactNode } from "react";
import { cn } from "@shared/lib/utils";

interface FormFieldProps extends InputHTMLAttributes<HTMLInputElement> {
    id: string;
    label: ReactNode;
    error?: string;
    hint?: string;
    labelClassName?: string;
    wrapperClassName?: string;
}

export const FormField = forwardRef<HTMLInputElement, FormFieldProps>(
    function FormField(
        { id, label, error, hint, required, className, labelClassName, wrapperClassName, ...inputProps },
        ref,
    ) {
        const errorId = error ? `${id}-error` : undefined;
        const hintId = hint ? `${id}-hint` : undefined;
        const describedBy = [hintId, errorId].filter(Boolean).join(" ") || undefined;

        return (
            <div className={wrapperClassName}>
                <label
                    htmlFor={id}
                    className={cn("block text-sm font-medium text-gray-700", labelClassName)}
                >
                    {label}
                    {required && <span aria-hidden="true" className="text-red-600"> *</span>}
                </label>
                {hint && (
                    <p id={hintId} className="mt-1 text-xs text-gray-600">
                        {hint}
                    </p>
                )}
                <input
                    ref={ref}
                    id={id}
                    required={required}
                    aria-required={required || undefined}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={describedBy}
                    className={cn(
                        "mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm",
                        "focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:border-primary-500",
                        error && "border-red-500 focus-visible:ring-red-500 focus-visible:border-red-500",
                        className,
                    )}
                    {...inputProps}
                />
                {error && (
                    <p id={errorId} role="alert" className="mt-1 text-xs text-red-600">
                        {error}
                    </p>
                )}
            </div>
        );
    },
);
```

- [ ] **Step 4: Run the test**

Run: `pnpm exec vitest run resources/js/shared/components/FormField.test.tsx`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/js/shared/components/FormField.tsx resources/js/shared/components/FormField.test.tsx
git commit -m "feat(a11y): add accessible FormField with aria-invalid/describedby"
```

---

## Task 4: Migrate Login + LoginModal to `FormField`

**Files:**
- Modify: `resources/js/system/pages/Login.tsx`
- Modify: `resources/js/system/components/LoginModal.tsx`

- [ ] **Step 1: Read current Login.tsx**

Run: `cat resources/js/system/pages/Login.tsx`
Identify each raw `<input>` and surrounding label/error markup. Note the names: `email`, `password`.

- [ ] **Step 2: Replace inputs with `FormField`**

In `resources/js/system/pages/Login.tsx`, import at top:

```tsx
import { FormField } from "@shared/components/FormField";
```

Replace each email/password input block. Example for email:

```tsx
<FormField
    id="email"
    label="E-mail"
    type="email"
    autoComplete="email"
    required
    value={email}
    onChange={(e) => setEmail(e.target.value)}
    error={errors.email}
/>
```

And for password:

```tsx
<FormField
    id="password"
    label="Senha"
    type={showPassword ? "text" : "password"}
    autoComplete="current-password"
    required
    value={password}
    onChange={(e) => setPassword(e.target.value)}
    error={errors.password}
/>
```

If a show/hide password button exists, set `type="button"` and add `aria-label={showPassword ? "Ocultar senha" : "Mostrar senha"}` and `aria-pressed={showPassword}`.

- [ ] **Step 3: Do the same in `LoginModal.tsx`, and set initial focus**

Repeat for `resources/js/system/components/LoginModal.tsx`. Add `autoFocus` on the email `FormField` OR pass an `onOpenAutoFocus` handler to the Radix `Dialog.Content` that calls `.focus()` on the email ref. Prefer the ref approach — it's reliable across remounts:

```tsx
const emailRef = useRef<HTMLInputElement>(null);
// ...
<Dialog.Content onOpenAutoFocus={(e) => { e.preventDefault(); emailRef.current?.focus(); }}>
    <FormField id="login-modal-email" ref={emailRef} label="E-mail" ... />
    ...
</Dialog.Content>
```

Use unique `id`s when the modal can coexist with another form on the same page (`login-modal-email`, `login-modal-password`).

- [ ] **Step 4: Typecheck**

Run: `pnpm run typecheck`
Expected: no errors.

- [ ] **Step 5: Lint the changed files**

Run: `pnpm exec eslint resources/js/system/pages/Login.tsx resources/js/system/components/LoginModal.tsx`
Expected: no jsx-a11y warnings.

- [ ] **Step 6: Commit**

```bash
git add resources/js/system/pages/Login.tsx resources/js/system/components/LoginModal.tsx
git commit -m "feat(a11y): migrate login form and modal to FormField"
```

---

## Task 5: Migrate Register to `FormField`

**Files:**
- Modify: `resources/js/system/pages/Register.tsx`

- [ ] **Step 1: Read the current file**

Run: `cat resources/js/system/pages/Register.tsx`
List every input and note: `name`, `email`, `password`, `password_confirmation`, plus any student fields.

- [ ] **Step 2: Replace every `<input>` with `<FormField>`**

For every input block, apply the same pattern as Task 4 Step 2. Map existing react-hook-form or local-state bindings into the `FormField` props. Keep existing validation logic — only the markup changes.

For `password_confirmation`, link the mismatch error directly to the confirm field:

```tsx
<FormField
    id="password_confirmation"
    label="Confirmar senha"
    type="password"
    autoComplete="new-password"
    required
    error={errors.password_confirmation ?? mismatchError}
    // ...
/>
```

Set `mismatchError` to `"As senhas não coincidem"` when the two fields diverge, computed in the component.

- [ ] **Step 3: Typecheck + lint**

Run: `pnpm run typecheck && pnpm exec eslint resources/js/system/pages/Register.tsx`
Expected: no errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/system/pages/Register.tsx
git commit -m "feat(a11y): migrate register form to FormField"
```

---

## Task 6: Migrate Profile sections to `FormField`

**Files:**
- Modify: `resources/js/system/components/profile/ProfileInfoSection.tsx`
- Modify: `resources/js/system/components/profile/PasswordSection.tsx`

- [ ] **Step 1: Replace inputs in ProfileInfoSection**

Apply the Task 4 pattern to each input in `ProfileInfoSection.tsx`. Keep the existing save/cancel flow untouched.

- [ ] **Step 2: Replace inputs in PasswordSection**

Same for `PasswordSection.tsx`: `current_password`, `password`, `password_confirmation`. Use `autoComplete="current-password"` for the current-password field and `autoComplete="new-password"` for the two new-password fields.

- [ ] **Step 3: Typecheck + lint**

Run: `pnpm run typecheck && pnpm exec eslint resources/js/system/components/profile/ProfileInfoSection.tsx resources/js/system/components/profile/PasswordSection.tsx`
Expected: no errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/system/components/profile/ProfileInfoSection.tsx resources/js/system/components/profile/PasswordSection.tsx
git commit -m "feat(a11y): migrate profile forms to FormField"
```

---

## Task 7: `LoadingRegion` + announce async loads

**Files:**
- Create: `resources/js/shared/components/LoadingRegion.tsx`
- Create: `resources/js/shared/components/LoadingRegion.test.tsx`
- Modify: `resources/js/system/pages/Home.tsx`
- Modify: `resources/js/system/pages/Presentations.tsx`
- Modify: `resources/js/system/pages/Subjects.tsx`
- Modify: `resources/js/system/pages/Workshops.tsx`
- Modify: `resources/js/system/pages/SeminarDetails.tsx`
- Modify: `resources/js/system/pages/WorkshopDetails.tsx`

- [ ] **Step 1: Write the failing test**

Create `resources/js/shared/components/LoadingRegion.test.tsx`:

```tsx
import { render, screen } from "@testing-library/react";
import { describe, it, expect } from "vitest";
import { LoadingRegion } from "./LoadingRegion";

describe("LoadingRegion", () => {
    it("exposes a polite status region with accessible label", () => {
        render(<LoadingRegion label="Carregando apresentações"><div /></LoadingRegion>);
        const region = screen.getByRole("status");
        expect(region).toHaveAttribute("aria-live", "polite");
        expect(region).toHaveAttribute("aria-busy", "true");
        expect(region).toHaveAccessibleName("Carregando apresentações");
    });

    it("renders children (skeletons) as visual content", () => {
        render(
            <LoadingRegion label="Carregando">
                <span data-testid="skeleton" />
            </LoadingRegion>,
        );
        expect(screen.getByTestId("skeleton")).toBeInTheDocument();
    });
});
```

- [ ] **Step 2: Run it**

Run: `pnpm exec vitest run resources/js/shared/components/LoadingRegion.test.tsx`
Expected: FAIL — module missing.

- [ ] **Step 3: Implement `LoadingRegion`**

Create `resources/js/shared/components/LoadingRegion.tsx`:

```tsx
import { ReactNode } from "react";

interface LoadingRegionProps {
    label: string;
    children: ReactNode;
    className?: string;
}

export function LoadingRegion({ label, children, className }: LoadingRegionProps) {
    return (
        <div
            role="status"
            aria-live="polite"
            aria-busy="true"
            aria-label={label}
            className={className}
        >
            {children}
        </div>
    );
}
```

- [ ] **Step 4: Run the test**

Run: `pnpm exec vitest run resources/js/shared/components/LoadingRegion.test.tsx`
Expected: PASS.

- [ ] **Step 5: Wrap skeleton blocks on each page**

For each of the listed pages, find the `isLoading` (or equivalent) branch that renders skeletons and wrap the returned JSX. Example for `Home.tsx`:

```tsx
import { LoadingRegion } from "@shared/components/LoadingRegion";

// ...
if (isLoading) {
    return (
        <LoadingRegion label="Carregando conteúdo da página inicial">
            {/* existing skeleton markup */}
        </LoadingRegion>
    );
}
```

Use a specific label per page: "Carregando apresentações", "Carregando tópicos", "Carregando workshops", "Carregando detalhes do seminário", "Carregando detalhes do workshop". Only wrap the loading branch — the loaded state does not need `aria-busy`.

- [ ] **Step 6: Typecheck + lint**

Run: `pnpm run typecheck && pnpm exec eslint "resources/js/system/pages/**/*.tsx" "resources/js/shared/components/LoadingRegion.tsx"`
Expected: no errors.

- [ ] **Step 7: Commit**

```bash
git add resources/js/shared/components/LoadingRegion.tsx resources/js/shared/components/LoadingRegion.test.tsx resources/js/system/pages/Home.tsx resources/js/system/pages/Presentations.tsx resources/js/system/pages/Subjects.tsx resources/js/system/pages/Workshops.tsx resources/js/system/pages/SeminarDetails.tsx resources/js/system/pages/WorkshopDetails.tsx
git commit -m "feat(a11y): announce loading states with LoadingRegion"
```

---

## Task 8: Icon buttons + contrast fixes

**Files:**
- Modify: `resources/js/system/components/CalendarMenu.tsx`
- Modify: `resources/js/system/components/SeminarDetails.tsx`
- Modify: `resources/js/system/components/ProfileInfoSection.tsx`
- Modify: any additional file where the baseline lint run (Task 1 Step 5) flagged `text-gray-400` as text color or a button lacks `aria-label`

- [ ] **Step 1: Re-run baseline lint**

Run: `pnpm exec eslint "resources/js/system/**/*.tsx"`
Expected: a (now small) list of jsx-a11y issues. Note each file and rule.

- [ ] **Step 2: Label icon-only links in CalendarMenu**

In `resources/js/system/components/CalendarMenu.tsx` (lines 54, 65, 75 per the audit), add an `aria-label` describing the destination, e.g. `aria-label="Abrir no Google Agenda (nova aba)"`, `aria-label="Abrir no Outlook (nova aba)"`, `aria-label="Baixar arquivo .ics"`. For external links, also add `rel="noopener noreferrer"` if missing.

Mark decorative icons `aria-hidden="true"`:

```tsx
<ExternalLink className="h-4 w-4" aria-hidden="true" />
```

- [ ] **Step 3: Raise text contrast**

Search for `text-gray-400` in the system SPA:

Run: `grep -rn "text-gray-400" resources/js/system resources/js/shared`

For each match that is *text* (not a decorative icon against a colored background), change to `text-gray-600`. Leave `text-gray-400` on decorative dividers or icons that sit on colored chips where contrast is already adequate. When unsure, change it.

- [ ] **Step 4: Ensure speaker social links have accessible names**

In `resources/js/system/components/SeminarDetails.tsx` (~lines 360, 375 per audit), ensure each social icon anchor has `aria-label="LinkedIn de {speaker.name}"` (or the appropriate network name) and that the inner icon has `aria-hidden="true"`.

- [ ] **Step 5: Typecheck + lint again**

Run: `pnpm run typecheck && pnpm exec eslint "resources/js/system/**/*.tsx"`
Expected: zero jsx-a11y errors.

- [ ] **Step 6: Commit**

```bash
git add -u resources/js/system resources/js/shared
git commit -m "fix(a11y): label icon-only controls and raise text contrast"
```

---

## Task 9: Full-suite verification

**Files:** none (verification only)

- [ ] **Step 1: Run the whole frontend test suite**

Run: `pnpm exec vitest run`
Expected: all tests pass.

- [ ] **Step 2: Run typecheck**

Run: `pnpm run typecheck`
Expected: no errors.

- [ ] **Step 3: Run lint across the system + shared SPA**

Run: `pnpm exec eslint "resources/js/system/**/*.{ts,tsx}" "resources/js/shared/**/*.{ts,tsx}"`
Expected: no jsx-a11y errors.

- [ ] **Step 4: Run backend tests (sanity — should be unaffected)**

Run: `php artisan test --compact`
Expected: all pass.

- [ ] **Step 5: Run Pint on any PHP that changed (sanity — likely none)**

Run: `vendor/bin/pint --dirty --format agent`
Expected: no-op or minor reformats.

- [ ] **Step 6: Manual smoke**

Ask the user to `composer run dev`, then walk through: tab from URL bar → skip link appears; activating it jumps to main content; navigate to `/login`, leave email blank, submit — screen reader / browser devtools should show `aria-invalid="true"` on the email field linked via `aria-describedby` to the error. Confirm mobile menu toggle announces `aria-expanded`.

- [ ] **Step 7: Final commit, if any formatting-only changes occurred**

```bash
git add -u
git diff --cached --quiet || git commit -m "chore(a11y): post-pass formatting"
```

---

## Out of Scope (Deliberate)

- **Admin SPA** — this plan does not touch `resources/js/admin/` even though similar issues likely exist.
- **Automated E2E axe sweep** — a Pest 4 browser test running `axe` against each route is a good follow-up, but is a separate effort.
- **Heading hierarchy audit** — the initial audit did not find h1/h2 ordering issues; revisit if a reviewer flags specific pages.
- **Color palette rework** — we raise individual contrast offenders but do not redesign Tailwind theme colors.
