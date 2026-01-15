# Migration Plan: CEFET-RJ Seminários System

## Target Stack
- **Laravel 11** with Vite + React (single repo, two entry points)
- **Laravel Socialite** for Google + GitHub OAuth
- **Spatie Laravel Permission** for unified role-based access
- **Intervention/Image** for certificate generation

---

## 1. Database Schema Catalog (Legacy → New)

### User Tables (3 tables → 1 unified `users` table)

| Legacy Table | Legacy Fields | New Table | New Fields |
|--------------|---------------|-----------|------------|
| `users` (admins) | id, name, user, email, password, remember_token, deleted_at, timestamps | `users` | id, name, email, username, password, remember_token, deleted_at, timestamps |
| `speakers` | id, name, institution, url, email, password, description, has_photo, remember_token, deleted_at, timestamps | `users` | + institution, profile_url, description, has_photo |
| `students` | id, name, email, password, has_photo, course_id, course_situation, type, remember_token, deleted_at, timestamps | `users` | + course_id, course_situation, student_type |

**New `users` table schema:**
```
id, name, email, username (nullable), password (nullable),
institution (nullable), profile_url (nullable), description (nullable), has_photo,
course_id (nullable), course_situation (nullable), student_type (nullable),
email_verified_at, remember_token, created_at, updated_at, deleted_at
```

### Core Domain Tables (Migrate as-is with minor renames)

| Legacy Table | New Table | Changes |
|--------------|-----------|---------|
| `courses` | `courses` | No changes |
| `locals` | `locals` | No changes |
| `workshops` | `workshops` | No changes |
| `type_seminaries` | `seminary_types` | Rename table |
| `subjects` | `subjects` | No changes |
| `seminaries` | `seminaries` | `type` → `seminary_type_id`, `combined_date` → `scheduled_at`, `url` → `slug` |
| `configs` | `configs` | No changes |

### Pivot Tables

| Legacy Table | New Table | Changes |
|--------------|-----------|---------|
| `students_seminaries` | `registrations` | `student_id` → `user_id`, add `id` primary key |
| `seminaries_speakers` | `seminary_speaker` | `speaker_id` → `user_id` |
| `seminaries_subjects` | `seminary_subject` | No changes |

### Supporting Tables

| Legacy Table | New Table | Changes |
|--------------|-----------|---------|
| `seminary_presence_links` | `presence_links` | No changes |
| `seminary_evaluations` | `evaluations` | `student_id` → `user_id` |
| `seminary_clicks` | `seminary_clicks` | `student_id` → `user_id` |
| `authy_accesses` | `social_identities` | Replace with Socialite structure |
| `mail_queues` | (removed) | Use Laravel native queue |

### Spatie Permission Tables (Fresh install)
- `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`

---

## 2. Database Configuration

### config/database.php - Legacy Connection

```php
'connections' => [
    'mysql' => [
        // New database (default)
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE', 'seminarios_new'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        // ...
    ],

    'legacy' => [
        // Legacy database (read-only for migration)
        'driver' => 'mysql',
        'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
        'database' => env('LEGACY_DB_DATABASE', 'seminarios_legacy'),
        'username' => env('LEGACY_DB_USERNAME', 'root'),
        'password' => env('LEGACY_DB_PASSWORD', ''),
        // ...
    ],
],
```

### .env Configuration
```
# New Database
DB_HOST=127.0.0.1
DB_DATABASE=seminarios_new
DB_USERNAME=root
DB_PASSWORD=

# Legacy Database
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_DATABASE=seminarios_legacy
LEGACY_DB_USERNAME=root
LEGACY_DB_PASSWORD=
```

---

## 3. Migration Scripts - Complete List

### Phase 1: Schema Migrations (database/migrations/)

```
2024_01_01_000001_create_courses_table.php
2024_01_01_000002_create_locals_table.php
2024_01_01_000003_create_seminary_types_table.php
2024_01_01_000004_create_subjects_table.php
2024_01_01_000005_create_workshops_table.php
2024_01_01_000006_create_users_table.php          # Unified users
2024_01_01_000007_create_seminaries_table.php
2024_01_01_000008_create_seminary_speaker_table.php
2024_01_01_000009_create_seminary_subject_table.php
2024_01_01_000010_create_registrations_table.php  # Was students_seminaries
2024_01_01_000011_create_presence_links_table.php
2024_01_01_000012_create_evaluations_table.php
2024_01_01_000013_create_seminary_clicks_table.php
2024_01_01_000014_create_social_identities_table.php
2024_01_01_000015_create_configs_table.php
2024_01_01_000016_create_permission_tables.php    # Spatie
2024_01_01_000017_create_user_id_mappings_table.php  # For migration tracking
```

### Phase 2: Data Migration Commands (app/Console/Commands/Migration/)

```
MigrateCoursesCommand.php
MigrateLocalsCommand.php
MigrateSeminaryTypesCommand.php
MigrateSubjectsCommand.php
MigrateWorkshopsCommand.php
MigrateUsersCommand.php           # Complex: merges 3 tables
MigrateSeminariesCommand.php
MigrateSeminarySpeakersCommand.php
MigrateSeminarySubjectsCommand.php
MigrateRegistrationsCommand.php   # Was students_seminaries
MigratePresenceLinksCommand.php
MigrateEvaluationsCommand.php
MigrateSeminaryClicksCommand.php
MigrateSocialIdentitiesCommand.php
MigrateConfigsCommand.php
MigrateRolesAndPermissionsCommand.php
```

---

## 4. Data Migration Command Examples

### Base Migration Command (Trait)

```php
// app/Console/Commands/Migration/Concerns/MigratesData.php

trait MigratesData
{
    protected function legacyTable(string $table)
    {
        return DB::connection('legacy')->table($table);
    }

    protected function newTable(string $table)
    {
        return DB::connection('mysql')->table($table);
    }

    protected function migrateSimpleTable(
        string $legacyTable,
        string $newTable,
        array $fieldMap = [],
        ?callable $transformer = null
    ): int {
        $count = 0;

        $this->legacyTable($legacyTable)
            ->orderBy('id')
            ->chunk(500, function ($records) use ($newTable, $fieldMap, $transformer, &$count) {
                $inserts = [];

                foreach ($records as $record) {
                    $data = (array) $record;

                    // Apply field mapping (rename columns)
                    foreach ($fieldMap as $old => $new) {
                        if (isset($data[$old])) {
                            $data[$new] = $data[$old];
                            unset($data[$old]);
                        }
                    }

                    // Apply custom transformer if provided
                    if ($transformer) {
                        $data = $transformer($data);
                    }

                    $inserts[] = $data;
                }

                // Use insert() to preserve timestamps
                $this->newTable($newTable)->insert($inserts);
                $count += count($inserts);
            });

        return $count;
    }
}
```

### Simple Table Migration (Courses)

```php
// app/Console/Commands/Migration/MigrateCoursesCommand.php

class MigrateCoursesCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:courses';
    protected $description = 'Migrate courses from legacy database';

    public function handle(): int
    {
        $this->info('Migrating courses...');

        // Clear existing data
        $this->newTable('courses')->truncate();

        // Migrate with preserved timestamps
        $count = $this->migrateSimpleTable('courses', 'courses');

        $this->info("Migrated {$count} courses.");
        return Command::SUCCESS;
    }
}
```

### Complex Table Migration (Users - 3 tables → 1)

```php
// app/Console/Commands/Migration/MigrateUsersCommand.php

class MigrateUsersCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:users {--dry-run : Show what would be migrated}';
    protected $description = 'Migrate and consolidate users, speakers, and students';

    public function handle(): int
    {
        $this->info('Migrating users (admins, speakers, students)...');

        if (!$this->option('dry-run')) {
            $this->newTable('users')->truncate();
            $this->newTable('user_id_mappings')->truncate();
        }

        // Step 1: Migrate admins (users table)
        $adminCount = $this->migrateAdmins();
        $this->info("Migrated {$adminCount} admins.");

        // Step 2: Migrate speakers
        $speakerCount = $this->migrateSpeakers();
        $this->info("Migrated {$speakerCount} speakers.");

        // Step 3: Migrate students
        $studentCount = $this->migrateStudents();
        $this->info("Migrated {$studentCount} students.");

        // Step 4: Assign roles
        $this->assignRoles();

        $total = $adminCount + $speakerCount + $studentCount;
        $this->info("Total: {$total} users migrated.");

        return Command::SUCCESS;
    }

    protected function migrateAdmins(): int
    {
        $count = 0;

        $this->legacyTable('users')
            ->orderBy('id')
            ->chunk(100, function ($admins) use (&$count) {
                foreach ($admins as $admin) {
                    $newId = $this->newTable('users')->insertGetId([
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'username' => $admin->user,
                        'password' => $admin->password,
                        'remember_token' => $admin->remember_token,
                        'has_photo' => false,
                        'created_at' => $admin->created_at,
                        'updated_at' => $admin->updated_at,
                        'deleted_at' => $admin->deleted_at,
                    ]);

                    // Store mapping for FK updates
                    $this->storeMapping('users', $admin->id, $newId, 'admin');
                    $count++;
                }
            });

        return $count;
    }

    protected function migrateSpeakers(): int
    {
        $count = 0;

        $this->legacyTable('speakers')
            ->orderBy('id')
            ->chunk(100, function ($speakers) use (&$count) {
                foreach ($speakers as $speaker) {
                    // Check if email already exists (admin might have same email)
                    $existing = $this->newTable('users')
                        ->where('email', $speaker->email)
                        ->first();

                    if ($existing) {
                        // Update existing user with speaker fields
                        $this->newTable('users')
                            ->where('id', $existing->id)
                            ->update([
                                'institution' => $speaker->institution,
                                'profile_url' => $speaker->url,
                                'description' => $speaker->description,
                                'has_photo' => $speaker->has_photo,
                            ]);

                        $this->storeMapping('speakers', $speaker->id, $existing->id, 'teacher');
                    } else {
                        $newId = $this->newTable('users')->insertGetId([
                            'name' => $speaker->name,
                            'email' => $speaker->email,
                            'password' => $speaker->password,
                            'institution' => $speaker->institution,
                            'profile_url' => $speaker->url,
                            'description' => $speaker->description,
                            'has_photo' => $speaker->has_photo,
                            'remember_token' => $speaker->remember_token,
                            'created_at' => $speaker->created_at,
                            'updated_at' => $speaker->updated_at,
                            'deleted_at' => $speaker->deleted_at,
                        ]);

                        $this->storeMapping('speakers', $speaker->id, $newId, 'teacher');
                    }
                    $count++;
                }
            });

        return $count;
    }

    protected function migrateStudents(): int
    {
        $count = 0;

        $this->legacyTable('students')
            ->orderBy('id')
            ->chunk(100, function ($students) use (&$count) {
                foreach ($students as $student) {
                    // Check if email already exists
                    $existing = $this->newTable('users')
                        ->where('email', $student->email)
                        ->first();

                    if ($existing) {
                        // Update with student fields
                        $this->newTable('users')
                            ->where('id', $existing->id)
                            ->update([
                                'course_id' => $student->course_id,
                                'course_situation' => $student->course_situation ? 'graduated' : 'studying',
                                'student_type' => $student->type,
                                'has_photo' => $existing->has_photo || $student->has_photo,
                            ]);

                        $this->storeMapping('students', $student->id, $existing->id, 'student');
                    } else {
                        $newId = $this->newTable('users')->insertGetId([
                            'name' => $student->name,
                            'email' => $student->email,
                            'password' => $student->password,
                            'has_photo' => $student->has_photo,
                            'course_id' => $student->course_id,
                            'course_situation' => $student->course_situation ? 'graduated' : 'studying',
                            'student_type' => $student->type,
                            'remember_token' => $student->remember_token,
                            'created_at' => $student->created_at,
                            'updated_at' => $student->updated_at,
                            'deleted_at' => $student->deleted_at,
                        ]);

                        $this->storeMapping('students', $student->id, $newId, 'student');
                    }
                    $count++;
                }
            });

        return $count;
    }

    protected function storeMapping(string $oldTable, int $oldId, int $newId, string $role): void
    {
        if (!$this->option('dry-run')) {
            $this->newTable('user_id_mappings')->insert([
                'legacy_table' => $oldTable,
                'legacy_id' => $oldId,
                'new_user_id' => $newId,
                'role' => $role,
                'created_at' => now(),
            ]);
        }
    }

    protected function assignRoles(): void
    {
        if ($this->option('dry-run')) return;

        $this->info('Assigning roles...');

        // Get role IDs
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        $teacherRole = DB::table('roles')->where('name', 'teacher')->first();
        $studentRole = DB::table('roles')->where('name', 'student')->first();

        // Assign roles based on mappings
        $mappings = $this->newTable('user_id_mappings')->get();

        foreach ($mappings as $map) {
            $roleId = match($map->role) {
                'admin' => $adminRole->id,
                'teacher' => $teacherRole->id,
                'student' => $studentRole->id,
            };

            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $map->new_user_id,
            ]);
        }
    }
}
```

### Migration with FK Remapping (Registrations)

```php
// app/Console/Commands/Migration/MigrateRegistrationsCommand.php

class MigrateRegistrationsCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:registrations';
    protected $description = 'Migrate students_seminaries to registrations';

    public function handle(): int
    {
        $this->info('Migrating registrations (students_seminaries)...');

        $this->newTable('registrations')->truncate();

        $count = 0;

        $this->legacyTable('students_seminaries')
            ->orderBy('student_id')
            ->orderBy('seminary_id')
            ->chunk(500, function ($records) use (&$count) {
                $inserts = [];

                foreach ($records as $record) {
                    // Get new user_id from mapping
                    $mapping = $this->newTable('user_id_mappings')
                        ->where('legacy_table', 'students')
                        ->where('legacy_id', $record->student_id)
                        ->first();

                    if (!$mapping) {
                        $this->warn("No mapping for student_id: {$record->student_id}");
                        continue;
                    }

                    $inserts[] = [
                        'seminary_id' => $record->seminary_id,
                        'user_id' => $mapping->new_user_id,
                        'present' => $record->present,
                        'reminder_sent' => $record->sentReminder,
                        'certificate_code' => $record->certificate_code,
                        'certificate_sent' => $record->sentCertificate ?? false,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ];
                }

                if (!empty($inserts)) {
                    $this->newTable('registrations')->insert($inserts);
                    $count += count($inserts);
                }
            });

        $this->info("Migrated {$count} registrations.");
        return Command::SUCCESS;
    }
}
```

### Seminary Speakers Migration (FK Remapping)

```php
// app/Console/Commands/Migration/MigrateSeminarySpeakersCommand.php

class MigrateSeminarySpeakersCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:seminary-speakers';
    protected $description = 'Migrate seminaries_speakers pivot table';

    public function handle(): int
    {
        $this->info('Migrating seminary speakers...');

        $this->newTable('seminary_speaker')->truncate();

        $count = 0;

        $this->legacyTable('seminaries_speakers')
            ->orderBy('seminary_id')
            ->chunk(500, function ($records) use (&$count) {
                $inserts = [];

                foreach ($records as $record) {
                    // Get new user_id from mapping
                    $mapping = $this->newTable('user_id_mappings')
                        ->where('legacy_table', 'speakers')
                        ->where('legacy_id', $record->speaker_id)
                        ->first();

                    if (!$mapping) {
                        $this->warn("No mapping for speaker_id: {$record->speaker_id}");
                        continue;
                    }

                    $inserts[] = [
                        'seminary_id' => $record->seminary_id,
                        'user_id' => $mapping->new_user_id,
                    ];
                }

                if (!empty($inserts)) {
                    $this->newTable('seminary_speaker')->insert($inserts);
                    $count += count($inserts);
                }
            });

        $this->info("Migrated {$count} seminary-speaker relationships.");
        return Command::SUCCESS;
    }
}
```

---

## 5. Master Migration Command

```php
// app/Console/Commands/Migration/MigrateAllCommand.php

class MigrateAllCommand extends Command
{
    protected $signature = 'migrate:legacy
                            {--fresh : Run fresh migrations first}
                            {--seed : Seed roles and permissions}';

    protected $description = 'Run all legacy data migrations in order';

    public function handle(): int
    {
        $this->info('Starting full legacy migration...');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->call('migrate:fresh');
        }

        if ($this->option('seed')) {
            $this->call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        }

        // Migration order (respects foreign key dependencies)
        $commands = [
            'migrate:courses',
            'migrate:locals',
            'migrate:seminary-types',
            'migrate:subjects',
            'migrate:workshops',
            'migrate:users',              // Must be before seminaries (speakers FK)
            'migrate:seminaries',
            'migrate:seminary-speakers',  // Requires users + seminaries
            'migrate:seminary-subjects',  // Requires subjects + seminaries
            'migrate:registrations',      // Requires users + seminaries
            'migrate:presence-links',     // Requires seminaries
            'migrate:evaluations',        // Requires users + seminaries
            'migrate:seminary-clicks',    // Requires users + seminaries
            'migrate:social-identities',  // Requires users
            'migrate:configs',
        ];

        foreach ($commands as $command) {
            $this->info("Running: {$command}");
            $this->call($command);
            $this->newLine();
        }

        $this->info('Legacy migration completed!');
        return Command::SUCCESS;
    }
}
```

---

## 6. User ID Mappings Table

```php
// database/migrations/2024_01_01_000017_create_user_id_mappings_table.php

Schema::create('user_id_mappings', function (Blueprint $table) {
    $table->id();
    $table->string('legacy_table');      // 'users', 'speakers', 'students'
    $table->unsignedBigInteger('legacy_id');
    $table->unsignedBigInteger('new_user_id');
    $table->string('role');              // 'admin', 'teacher', 'student'
    $table->timestamp('created_at');

    $table->unique(['legacy_table', 'legacy_id']);
    $table->index('new_user_id');
    $table->foreign('new_user_id')->references('id')->on('users');
});
```

---

## 7. Roles and Permissions Seeder

```php
// database/seeders/RolesAndPermissionsSeeder.php

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Admin permissions
            'manage users',
            'manage seminaries',
            'manage speakers',
            'manage students',
            'manage courses',
            'manage locals',
            'manage workshops',
            'manage subjects',
            'manage types',
            'manage config',
            'manage documents',
            'manage marketing',
            'view evaluations',
            'manage evaluations',
            'run commands',

            // Teacher permissions (subset)
            'view seminaries',
            'edit own seminaries',

            // Student permissions
            'subscribe seminaries',
            'view certificates',
            'submit evaluations',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $teacher = Role::create(['name' => 'teacher']);
        $teacher->givePermissionTo([
            'view seminaries',
            'edit own seminaries',
        ]);

        $student = Role::create(['name' => 'student']);
        $student->givePermissionTo([
            'subscribe seminaries',
            'view certificates',
            'submit evaluations',
        ]);
    }
}
```

---

## 8. Key Functionalities Migration

### QR Code Presence System
- **Current:** `SeminaryPresenceLink` model with custom QR service
- **New:** Same logic, updated to use unified `users` table
- **Route:** `GET /p/{presenceLink:uuid}` → marks `user_id` as present in `registrations`

### Certificate Generation
- **Current:** Intervention/Image in `CertificateController`
- **New:** `CertificateService` + `GenerateCertificateJob`
- **Preserve:** Certificate code format `XXXX-XXXX-XXXX-XXXXXXXXXX`
- **Assets:** Copy `storage/app/assets/certificate/` to new project

### Cronjobs (Laravel 11 Scheduler)
```php
// bootstrap/app.php
->withSchedule(function (Schedule $schedule) {
    $schedule->command('certificates:process --send')->everyThirtyMinutes();
    $schedule->command('reminders:send')->hourly();
    $schedule->command('presence-links:purge')->hourly();
})
```

### Authentication
- **Remove:** Custom Authy system entirely
- **Add:** Laravel Socialite with Google + GitHub
- **Store:** OAuth data in `social_identities` table
- **API:** Laravel Sanctum for SPA authentication

---

## 9. React SPA Structure

### Single Repo, Two Entry Points

```
resources/js/
├── admin/                    # Admin SPA
│   ├── app.tsx
│   ├── pages/
│   │   ├── Dashboard.tsx
│   │   ├── seminaries/
│   │   ├── users/
│   │   ├── students/
│   │   ├── speakers/
│   │   └── ...
│   └── components/
├── system/                   # Public/Student SPA
│   ├── app.tsx
│   ├── pages/
│   │   ├── Home.tsx
│   │   ├── seminaries/
│   │   ├── auth/
│   │   └── student/
│   └── components/
└── shared/                   # Shared code
    ├── components/
    ├── hooks/
    ├── services/
    └── types/
```

### Vite Config
```typescript
// vite.config.ts
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/admin/app.tsx',
                'resources/js/system/app.tsx',
            ],
        }),
        react(),
    ],
});
```

---

## 10. Admin Views to Migrate

| Current Blade View | New React Component | Route |
|--------------------|---------------------|-------|
| `adm/dashboard` | `admin/pages/Dashboard.tsx` | `/admin` |
| `adm/modules/seminaries/*` | `admin/pages/seminaries/*` | `/admin/seminaries` |
| `adm/modules/students/*` | `admin/pages/students/*` | `/admin/students` |
| `adm/modules/speakers/*` | `admin/pages/speakers/*` | `/admin/speakers` |
| `adm/modules/courses/*` | `admin/pages/courses/*` | `/admin/courses` |
| `adm/modules/locals/*` | `admin/pages/locals/*` | `/admin/locals` |
| `adm/modules/subjects/*` | `admin/pages/subjects/*` | `/admin/subjects` |
| `adm/modules/seminary-types/*` | `admin/pages/types/*` | `/admin/types` |
| `adm/modules/workshops/*` | `admin/pages/workshops/*` | `/admin/workshops` |
| `adm/modules/users/*` | `admin/pages/users/*` | `/admin/users` |
| `adm/modules/subscriptions/*` | `admin/pages/subscriptions/*` | `/admin/subscriptions` |
| `adm/documents/*` | `admin/pages/documents/*` | `/admin/documents` |
| `adm/config/*` | `admin/pages/config/*` | `/admin/config` |
| `adm/calendar/*` | `admin/pages/Calendar.tsx` | `/admin/calendar` |

---

## 11. Implementation Phases

### Phase 1: Project Setup
- [ ] Create Laravel 11 project
- [ ] Configure Vite with React + TypeScript
- [ ] Install packages: Spatie Permission, Socialite, Sanctum, Intervention/Image
- [ ] Configure database connections (new + legacy)

### Phase 2: Schema Migrations
- [ ] Create all schema migration files
- [ ] Create `user_id_mappings` table
- [ ] Run `php artisan migrate`
- [ ] Seed roles and permissions

### Phase 3: Data Migration Commands
- [ ] Create `MigratesData` trait
- [ ] Create individual migration commands
- [ ] Create `migrate:legacy` master command
- [ ] Test with `--dry-run` flag

### Phase 4: Execute Data Migration
- [ ] Run `php artisan migrate:legacy --fresh --seed`
- [ ] Verify data integrity
- [ ] Check user consolidation
- [ ] Verify FK relationships

### Phase 5: Backend API
- [ ] Implement authentication (Sanctum + Socialite)
- [ ] Create API controllers
- [ ] Create API resources
- [ ] Implement policies

### Phase 6: React SPAs
- [ ] Build shared components
- [ ] Build Admin SPA
- [ ] Build System SPA

### Phase 7: Background Jobs
- [ ] Migrate certificate generation
- [ ] Migrate reminder system
- [ ] Configure scheduler

### Phase 8: Verification
- [ ] Test all CRUD operations
- [ ] Test QR presence flow
- [ ] Test certificate generation
- [ ] Run cronjobs manually
- [ ] End-to-end testing

---

## 12. Verification Checklist

### Data Integrity
- [ ] All courses migrated with original IDs
- [ ] All locals migrated with original IDs
- [ ] All seminary types migrated
- [ ] All subjects migrated with original IDs
- [ ] All workshops migrated with original IDs
- [ ] All users consolidated with correct roles
- [ ] All seminaries migrated with updated FKs
- [ ] All registrations migrated with user_id remapping
- [ ] All evaluations migrated with user_id remapping
- [ ] All presence links migrated
- [ ] Certificate codes preserved

### Functionality
- [ ] Admin can login
- [ ] Teacher can login (with teacher role)
- [ ] Student can login via Google/GitHub
- [ ] Student can subscribe to seminary
- [ ] QR presence marking works
- [ ] Certificate generation produces valid JPG
- [ ] Reminder emails sent correctly
- [ ] Admin CRUD for all entities works

---

## Critical Files Reference

### Legacy Project (to reference)
- [User.php](app/Core/User/User.php) - Admin model with Spatie
- [Speaker.php](app/Core/Speaker/Speaker.php) - Teacher model
- [Student.php](app/Core/Student/Student.php) - Student model
- [Seminary.php](app/Core/Seminary/Seminary.php) - Main event model
- [CertificateController.php](app/Http/Controllers/Seminary/CertificateController.php) - Certificate logic
- [Kernel.php](app/Console/Kernel.php) - Scheduler config
- [web.php](routes/web.php) - Route structure
- [auth.php](config/auth.php) - Guards config
