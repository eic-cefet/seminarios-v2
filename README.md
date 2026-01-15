# CEFET-RJ Seminários

A seminar and workshop management system for the School of Informatics & Computing (EIC) at CEFET-RJ.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** React 19, TypeScript, Tailwind CSS 4
- **Database:** MySQL
- **Testing:** Pest

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- pnpm
- MySQL

## Installation

```bash
# Clone the repository
git clone https://github.com/jorgejr568/seminarios-eic.git
cd seminarios-eic

# Run the setup script (installs dependencies, generates key, runs migrations, builds assets)
composer run setup
```

Or manually:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
pnpm install
pnpm run build
```

## Development

```bash
# Start all services (server, queue, logs, vite)
composer run dev
```

This runs concurrently:
- Laravel development server (`php artisan serve`)
- Queue worker (`php artisan queue:listen`)
- Log viewer (`php artisan pail`)
- Vite dev server (`pnpm run dev`)

## Testing

```bash
# Run all tests
composer run test

# Or directly with artisan
php artisan test
```

## Legacy Data Migration

To migrate data from the legacy database:

```bash
# Configure LEGACY_DB_* variables in .env, then:
php artisan migrate:legacy --fresh --seed
```

## Project Structure

```
resources/js/
├── admin/      # Admin panel SPA
├── system/     # Public-facing SPA
└── shared/     # Shared components, API client, types
```

## License

MIT
