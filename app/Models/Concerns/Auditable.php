<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

trait Auditable
{
    protected static array $globalAuditExclude = [
        'password',
        'remember_token',
        'token',
        'refresh_token',
        'two_factor_secret',
    ];

    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->logAuditEvent('created', $model->getAuditableAttributes());
        });

        static::updated(function ($model) {
            $changed = $model->getDirty();
            $excluded = $model->getAuditExcludedFields();
            $meaningful = array_diff_key($changed, array_flip($excluded), array_flip(['updated_at']));

            if (empty($meaningful)) {
                return;
            }

            $oldValues = [];
            $newValues = [];
            foreach ($meaningful as $key => $value) {
                $oldValues[$key] = $model->getOriginal($key);
                $newValues[$key] = $value;
            }

            $model->logAuditEvent('updated', [
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        });

        static::deleted(function ($model) {
            $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model));
            $isSoftDeleting = $usesSoftDeletes && ! $model->isForceDeleting();

            $model->logAuditEvent(
                $isSoftDeleting ? 'soft_deleted' : 'deleted',
                $model->getAuditableAttributes(),
            );
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                $model->logAuditEvent('restored', $model->getAuditableAttributes());
            });

            static::forceDeleted(function ($model) {
                $model->logAuditEvent('force_deleted', $model->getAuditableAttributes());
            });
        }
    }

    protected function logAuditEvent(string $action, array $eventData): void
    {
        AuditLog::recordModelEvent(
            eventName: $this->getAuditEventName($action),
            auditable: $this,
            eventData: $eventData,
        );
    }

    protected function getAuditEventName(string $action): string
    {
        return Str::snake(class_basename(static::class)).".{$action}";
    }

    protected function getAuditableAttributes(): array
    {
        return array_diff_key($this->getAttributes(), array_flip($this->getAuditExcludedFields()));
    }

    protected function getAuditExcludedFields(): array
    {
        $modelExclude = property_exists($this, 'auditExclude') ? $this->auditExclude : [];

        return array_unique(array_merge(static::$globalAuditExclude, $modelExclude));
    }
}
