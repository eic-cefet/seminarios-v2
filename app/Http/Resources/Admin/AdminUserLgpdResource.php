<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserLgpdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'anonymization_requested_at' => $this->anonymization_requested_at?->toIso8601String(),
            'anonymized_at' => $this->anonymized_at?->toIso8601String(),
            'consents' => $this->consents->map(fn ($c) => [
                'type' => $c->type->value,
                'granted' => $c->granted,
                'version' => $c->version,
                'source' => $c->source,
                'created_at' => $c->created_at?->toIso8601String(),
            ])->values(),
            'data_export_requests' => $this->dataExportRequests->map(fn ($r) => [
                'id' => $r->id,
                'status' => $r->status,
                'created_at' => $r->created_at?->toIso8601String(),
                'completed_at' => $r->completed_at?->toIso8601String(),
                'expires_at' => $r->expires_at?->toIso8601String(),
            ])->values(),
        ];
    }
}
