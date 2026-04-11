<?php

namespace App\Models;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Context;

class AuditLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'event_name',
        'event_type',
        'auditable_type',
        'auditable_id',
        'event_data',
        'origin',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'event_data' => 'array',
            'event_type' => AuditEventType::class,
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Record an audit log entry for manual/explicit events.
     */
    public static function record(
        AuditEvent $event,
        AuditEventType $eventType = AuditEventType::Manual,
        ?Model $auditable = null,
        array $eventData = [],
        ?int $userId = null,
    ): self {
        return self::log($event->value, $eventType, $auditable, $eventData, $userId);
    }

    /**
     * Record a model lifecycle event (used internally by the Auditable trait).
     */
    public static function recordModelEvent(
        string $eventName,
        Model $auditable,
        array $eventData = [],
    ): self {
        return self::log($eventName, AuditEventType::System, $auditable, $eventData);
    }

    protected static function log(
        string $eventName,
        AuditEventType $eventType,
        ?Model $auditable,
        array $eventData,
        ?int $userId = null,
    ): self {
        $origin = Context::get('audit.origin');

        if ($origin === null) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            foreach ($trace as $frame) {
                $class = $frame['class'] ?? null;
                if ($class && $class !== self::class && ! str_starts_with($class, 'Illuminate\\')) {
                    $origin = class_basename($class).'@'.($frame['function'] ?? 'unknown');
                    break;
                }
            }
        }

        $ipAddress = Context::get('audit.ip');

        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'event_name' => $eventName,
            'event_type' => $eventType,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'event_data' => $eventData ?: null,
            'origin' => $origin,
            'ip_address' => $ipAddress,
        ]);
    }

    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query
            ->where('auditable_type', $model->getMorphClass())
            ->where('auditable_id', $model->getKey());
    }

    public function scopeForEvent(Builder $query, string|AuditEvent $eventName): Builder
    {
        return $query->where('event_name', $eventName instanceof AuditEvent ? $eventName->value : $eventName);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBetween(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
