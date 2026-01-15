<?php

namespace App\Console\Commands\Migration;

use App\Console\Commands\Migration\Concerns\MigratesData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateUsersCommand extends Command
{
    use MigratesData;

    protected $signature = 'migrate:users {--dry-run : Show what would be migrated}';
    protected $description = 'Migrate and consolidate users, speakers, and students';

    public function handle(): int
    {
        $this->info('Migrating users (admins, speakers, students)...');

        if (!$this->option('dry-run')) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $this->newTable('users')->truncate();
            $this->newTable('user_student_data')->truncate();
            $this->newTable('user_speaker_data')->truncate();
            $this->newTable('user_id_mappings')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $adminCount = $this->migrateAdmins();
        $this->info("Migrated {$adminCount} admins.");

        $speakerCount = $this->migrateSpeakers();
        $this->info("Migrated {$speakerCount} speakers.");

        $studentCount = $this->migrateStudents();
        $this->info("Migrated {$studentCount} students.");

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
                    if ($this->option('dry-run')) {
                        $this->line("Would migrate admin: {$admin->email}");
                        $count++;
                        continue;
                    }

                    $newId = $this->newTable('users')->insertGetId([
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'username' => $admin->user ?? null,
                        'password' => $admin->password,
                        'remember_token' => $admin->remember_token,
                        'created_at' => $admin->created_at,
                        'updated_at' => $admin->updated_at,
                        'deleted_at' => $admin->deleted_at ?? null,
                    ]);

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
                    if ($this->option('dry-run')) {
                        $this->line("Would migrate speaker: {$speaker->email}");
                        $count++;
                        continue;
                    }

                    $existing = $this->newTable('users')
                        ->where('email', $speaker->email)
                        ->first();

                    if ($existing) {
                        $existingSpeakerData = $this->newTable('user_speaker_data')
                            ->where('user_id', $existing->id)
                            ->exists();

                        if (!$existingSpeakerData) {
                            $this->newTable('user_speaker_data')->insert([
                                'user_id' => $existing->id,
                                'slug' => $speaker->url ? Str::slug($speaker->url) : null,
                                'institution' => $speaker->institution,
                                'description' => $speaker->description,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        $this->storeMapping('speakers', $speaker->id, $existing->id, 'teacher');
                    } else {
                        $newId = $this->newTable('users')->insertGetId([
                            'name' => $speaker->name,
                            'email' => $speaker->email,
                            'password' => $speaker->password,
                            'remember_token' => $speaker->remember_token,
                            'created_at' => $speaker->created_at,
                            'updated_at' => $speaker->updated_at,
                            'deleted_at' => $speaker->deleted_at ?? null,
                        ]);

                        $this->newTable('user_speaker_data')->insert([
                            'user_id' => $newId,
                            'slug' => $speaker->url ? Str::slug($speaker->url) : null,
                            'institution' => $speaker->institution,
                            'description' => $speaker->description,
                            'created_at' => now(),
                            'updated_at' => now(),
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
                    if ($this->option('dry-run')) {
                        $this->line("Would migrate student: {$student->email}");
                        $count++;
                        continue;
                    }

                    $courseName = $this->getCourseName($student->course_id);

                    $existing = $this->newTable('users')
                        ->where('email', $student->email)
                        ->first();

                    if ($existing) {
                        $existingStudentData = $this->newTable('user_student_data')
                            ->where('user_id', $existing->id)
                            ->exists();

                        if (!$existingStudentData) {
                            $this->newTable('user_student_data')->insert([
                                'user_id' => $existing->id,
                                'course_name' => $courseName,
                                'course_situation' => $student->course_situation ? 'graduated' : 'studying',
                                'course_role' => $student->type,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        $this->storeMapping('students', $student->id, $existing->id, 'student');
                    } else {
                        $newId = $this->newTable('users')->insertGetId([
                            'name' => $student->name,
                            'email' => $student->email,
                            'password' => $student->password,
                            'remember_token' => $student->remember_token,
                            'created_at' => $student->created_at,
                            'updated_at' => $student->updated_at,
                            'deleted_at' => $student->deleted_at ?? null,
                        ]);

                        $this->newTable('user_student_data')->insert([
                            'user_id' => $newId,
                            'course_name' => $courseName,
                            'course_situation' => $student->course_situation ? 'graduated' : 'studying',
                            'course_role' => $student->type,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $this->storeMapping('students', $student->id, $newId, 'student');
                    }
                    $count++;
                }
            });

        return $count;
    }

    protected function getCourseName(?int $courseId): ?string
    {
        if (!$courseId) {
            return null;
        }

        $course = $this->legacyTable('courses')->find($courseId);
        return $course?->name;
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
        if ($this->option('dry-run')) {
            return;
        }

        $this->info('Assigning roles...');

        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        $teacherRole = DB::table('roles')->where('name', 'teacher')->first();
        $studentRole = DB::table('roles')->where('name', 'student')->first();

        if (!$adminRole || !$teacherRole || !$studentRole) {
            $this->warn('Roles not found. Run db:seed first.');
            return;
        }

        $mappings = $this->newTable('user_id_mappings')->get();

        foreach ($mappings as $map) {
            $roleId = match ($map->role) {
                'admin' => $adminRole->id,
                'teacher' => $teacherRole->id,
                'student' => $studentRole->id,
                default => null,
            };

            if ($roleId) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $map->new_user_id,
                ]);
            }
        }
    }
}
