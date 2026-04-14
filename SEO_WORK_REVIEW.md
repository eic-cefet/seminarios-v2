# SEO Work Review

This file was added only to make cross-machine review easier and can be deleted before merge.

## What Was Added

- Public XML sitemap at `/sitemap.xml`
- `robots.txt` updates pointing crawlers to the sitemap
- Shared SEO metadata component for React public pages
- Structured data (`schema.org` JSON-LD) for key public routes
- `noindex, nofollow` protection for admin and non-indexable utility/auth pages
- Public shell language/title defaults aligned with the site content
- Test coverage for the sitemap and SEO metadata helpers

## Sitemap

Implemented in:

- `app/Http/Controllers/SitemapController.php`
- `resources/views/sitemap.blade.php`
- `routes/web.php`

Behavior:

- Includes only public, crawl-worthy routes
- Includes static public routes:
  - `/`
  - `/apresentacoes`
  - `/topicos`
  - `/workshops`
- Includes dynamic public detail routes:
  - `/seminario/{slug}` for active seminars
  - `/topico/{id}` for subjects that have active seminars
  - `/workshop/{id}` for workshops that have active seminars
- Excludes auth/admin/utility pages
- Emits `lastmod` where available

## Metadata Layer

Expanded `resources/js/shared/components/PageTitle.tsx` so pages can set:

- Title
- Meta description
- Canonical URL
- `robots`
- Open Graph tags
- Twitter card tags
- JSON-LD structured data

Added utility helpers in `resources/js/shared/lib/utils.ts`:

- `buildAbsoluteUrl`
- `stripHtml`
- `truncateText`

## Public Pages Updated

### Indexable pages

- `resources/js/system/pages/Home.tsx`
- `resources/js/system/pages/Presentations.tsx`
- `resources/js/system/pages/Subjects.tsx`
- `resources/js/system/pages/SubjectSeminars.tsx`
- `resources/js/system/pages/Workshops.tsx`
- `resources/js/system/pages/WorkshopDetails.tsx`
- `resources/js/system/pages/SeminarDetails.tsx`

### Non-indexable pages explicitly marked

- `resources/js/system/pages/Login.tsx`
- `resources/js/system/pages/Register.tsx`
- `resources/js/system/pages/ForgotPassword.tsx`
- `resources/js/system/pages/ResetPassword.tsx`
- `resources/js/system/pages/Profile.tsx`
- `resources/js/system/pages/Certificates.tsx`
- `resources/js/system/pages/Evaluations.tsx`
- `resources/js/system/pages/BugReport.tsx`
- `resources/js/system/pages/AuthCallback.tsx`
- `resources/js/system/pages/Presence.tsx`
- `resources/js/system/pages/NotFound.tsx`

## Structured Data Used

### Home

- `WebSite`
- `Organization`

### Presentation / topic / workshop listing and detail collection pages

- `CollectionPage`
- `ItemList`
- `BreadcrumbList` on detail pages

### Seminar detail page

- `Event`
- `BreadcrumbList`

## Blade / Public Shell Changes

- `resources/views/system.blade.php`
  - `lang="pt-BR"`
  - better default title for the public shell

- `resources/views/admin.blade.php`
  - `lang="pt-BR"`
  - `meta name="robots" content="noindex, nofollow"`

- `public/robots.txt`
  - explicit sitemap line
  - blocks obvious non-public paths from crawl

## Tests Added / Updated

### Backend

- `tests/Feature/SitemapTest.php`
- `tests/Feature/ExampleTest.php`

### Frontend

- `resources/js/shared/components/PageTitle.test.tsx`
- `resources/js/shared/lib/utils.test.ts`
- `resources/js/system/pages/Home.test.tsx`
- `resources/js/system/pages/SeminarDetails.test.tsx`

## Verification Already Run

- `php artisan test --compact tests/Feature/SitemapTest.php tests/Feature/ExampleTest.php`
- `pnpm exec vitest run resources/js/shared/components/PageTitle.test.tsx resources/js/shared/lib/utils.test.ts resources/js/system/pages/Home.test.tsx resources/js/system/pages/SeminarDetails.test.tsx`
- `pnpm run typecheck`
- `vendor/bin/pint --dirty --format agent`
- `php artisan test --compact`
- `pnpm exec vitest run`
- `pnpm run typecheck`

## Notes

- Full backend and frontend suites passed after the changes.
- Frontend test output still includes pre-existing warnings around dialog descriptions and some `act(...)` warnings in unrelated tests.
