# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CEFET-RJ Seminários - A seminar and workshop management system for the School of Informatics & Computing (EIC). Built with Laravel 12 backend and dual React 19 SPAs (public system + admin panel).

## Common Commands

```bash
# Development (runs server, queue, logs, and vite concurrently)
composer run dev

# Build frontend assets
pnpm run build

# Run tests
php artisan test --compact
php artisan test --compact --filter=testName
php artisan test --compact tests/Feature/ExampleTest.php

# Format PHP code
vendor/bin/pint --dirty

# Legacy data migration (from old database)
php artisan migrate:legacy --fresh --seed
```

## Architecture

### Dual SPA Architecture

The application has two separate React SPAs served by Laravel:

- **System SPA** (`resources/js/system/`) - Public-facing for students/attendees
  - Routes: `/`, `/login`, `/cadastro`, `/disciplinas`, `/apresentacoes`, `/seminario/:slug`, `/workshops`, `/workshop/:id`
  - Served at `/*` (catch-all except admin/api paths)

- **Admin SPA** (`resources/js/admin/`) - Protected dashboard for administrators
  - Routes under `/admin/*`
  - Requires authentication middleware

### Shared Code

`resources/js/shared/` contains code used by both SPAs:
- `api/client.ts` - API client with typed endpoints (seminarsApi, subjectsApi, workshopsApi, etc.)
- `types/index.ts` - TypeScript interfaces for API responses
- `lib/utils.ts` - Utility functions (cn, formatDateTime, containsHTML)

### Vite Path Aliases

```typescript
'@'       → resources/js/
'@admin'  → resources/js/admin/
'@system' → resources/js/system/
'@shared' → resources/js/shared/
```

### Backend API Structure

All public API routes are in `routes/api.php` under `/api/*`:
- Controllers: `app/Http/Controllers/Api/`
- Resources: `app/Http/Resources/` (SeminarResource, SubjectResource, etc.)

### Domain Models

Core entities: Seminar, Subject, Workshop, SeminarType, User, Registration, Rating
- Seminars belong to Workshops (optional), have many Subjects (pivot), and Speakers (pivot with UserSpeakerData)
- Users can be speakers (UserSpeakerData) or students (UserStudentData)

### Legacy Migration System

Data is migrated from a legacy database using artisan commands in `app/Console/Commands/Migration/`:
- `php artisan migrate:legacy` runs all migrations in order
- Individual commands: `migrate:seminars`, `migrate:users`, `migrate:workshops`, etc.
- Legacy DB connection configured as `legacy` in `config/database.php`

## Key Conventions

### Frontend
- Use TanStack Query for data fetching
- Use Radix UI primitives for accessible components
- Tailwind CSS v4 (CSS-first config with `@theme` directive)
- Lucide React for icons

### Backend
- API Resources for JSON transformation
- Eloquent with eager loading (avoid N+1)
- Form Request classes for validation
- Pest for testing

### OAuth Flow
Social auth uses one-time code exchange pattern:
1. `/auth/{provider}` → OAuth provider
2. Provider callback generates code stored in cache
3. Frontend exchanges code via `POST /api/auth/exchange`
