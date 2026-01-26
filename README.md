<div align="center">

# CEFET-RJ Seminários

**A modern seminar and workshop management system for the School of Informatics & Computing (EIC) at CEFET-RJ**

[![Tests](https://github.com/eic-cefet/seminarios-v2/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/eic-cefet/seminarios-v2/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/eic-cefet/seminarios-v2/graph/badge.svg)](https://codecov.io/gh/eic-cefet/seminarios-v2)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![React 19](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)](https://react.dev/)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.7-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![Tailwind CSS 4](https://img.shields.io/badge/Tailwind-4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

</div>

---

## Tech Stack

<table>
<tr>
<td align="center" width="150">

[![PHP](https://img.shields.io/badge/-PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/docs.php)

**8.4+**

</td>
<td align="center" width="150">

[![Laravel](https://img.shields.io/badge/-Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/docs)

**v12**

</td>
<td align="center" width="150">

[![React](https://img.shields.io/badge/-React-61DAFB?style=for-the-badge&logo=react&logoColor=black)](https://react.dev/)

**v19**

</td>
<td align="center" width="150">

[![TypeScript](https://img.shields.io/badge/-TypeScript-3178C6?style=for-the-badge&logo=typescript&logoColor=white)](https://www.typescriptlang.org/docs/)

**5.7+**

</td>
</tr>
<tr>
<td align="center" width="150">

[![Tailwind CSS](https://img.shields.io/badge/-Tailwind-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/docs)

**v4**

</td>
<td align="center" width="150">

[![Vite](https://img.shields.io/badge/-Vite-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vite.dev/)

**v7**

</td>
<td align="center" width="150">

[![MySQL](https://img.shields.io/badge/-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://dev.mysql.com/doc/)

**8.0**

</td>
<td align="center" width="150">

[![Pest](https://img.shields.io/badge/-Pest-F9322C?style=for-the-badge&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZmlsbD0id2hpdGUiIGQ9Ik0xMiAyQzYuNDggMiAyIDYuNDggMiAxMnM0LjQ4IDEwIDEwIDEwIDEwLTQuNDggMTAtMTBTMTcuNTIgMiAxMiAyem0wIDE4Yy00LjQxIDAtOC0zLjU5LTgtOHMzLjU5LTggOC04IDggMy41OSA4IDgtMy41OSA4LTggOHoiLz48L3N2Zz4=&logoColor=white)](https://pestphp.com/docs)

**v4**

</td>
</tr>
</table>

### Additional Tools & Libraries

| Category | Technologies |
|----------|-------------|
| **Authentication** | [Sanctum](https://laravel.com/docs/sanctum) · [Socialite](https://laravel.com/docs/socialite) (Google OAuth) |
| **UI Components** | [Radix UI](https://www.radix-ui.com/) · [Lucide Icons](https://lucide.dev/) |
| **Data Fetching** | [TanStack Query](https://tanstack.com/query) · [Zod](https://zod.dev/) |
| **Forms** | [React Hook Form](https://react-hook-form.com/) |
| **PDF/Excel** | [DomPDF](https://github.com/barryvdh/laravel-dompdf) · [Maatwebsite Excel](https://docs.laravel-excel.com/) |
| **Queue/Jobs** | [Laravel Queues](https://laravel.com/docs/queues) |
| **Authorization** | [Spatie Permission](https://spatie.be/docs/laravel-permission) |

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.2+ |
| Composer | Latest |
| Node.js | 18+ |
| pnpm | Latest |
| MySQL | 8.0+ |

---

## Quick Start

```bash
# Clone the repository
git clone https://github.com/eic-cefet/seminarios-v2.git
cd seminarios-v2

# Run the setup script (installs dependencies, generates key, runs migrations, builds assets)
composer run setup
```

<details>
<summary><strong>Manual Installation</strong></summary>

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
pnpm install
pnpm run build
```

</details>

---

## Development

```bash
# Start all services (server, queue, logs, vite)
composer run dev
```

This runs concurrently:
- 🌐 Laravel development server (`php artisan serve`)
- 📬 Queue worker (`php artisan queue:listen`)
- 📋 Log viewer (`php artisan pail`)
- ⚡ Vite dev server (`pnpm run dev`)

---

## Testing

```bash
# Run all tests
composer run test

# Or with specific filter
php artisan test --compact --filter=testName

# Run specific test file
php artisan test --compact tests/Feature/ExampleTest.php
```

---

## Architecture

```
resources/js/
├── admin/      # 🔐 Admin panel SPA (protected dashboard)
├── system/     # 🌍 Public-facing SPA (students/attendees)
└── shared/     # 🔗 Shared components, API client, types
```

### Dual SPA Architecture

| SPA | Description | Routes |
|-----|-------------|--------|
| **System** | Public-facing for students/attendees | `/`, `/login`, `/cadastro`, `/topicos`, `/apresentacoes`, `/seminario/:slug`, `/workshops`, `/workshop/:id` |
| **Admin** | Protected dashboard for administrators | `/admin/*` |

---

## Legacy Data Migration

```bash
# Configure LEGACY_DB_* variables in .env, then:
php artisan migrate:legacy --fresh --seed
```

---

## License

[MIT](https://opensource.org/licenses/MIT)

---

<div align="center">

Made with ❤️ for **CEFET-RJ EIC**

</div>
