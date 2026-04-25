<?php

namespace App\Http\Resources\Admin;

use App\Models\PresenceLink;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PresenceLink
 */
class PresenceLinkResource extends JsonResource
{
    public function __construct(
        PresenceLink $resource,
        protected bool $includeQrCode = false,
        protected ?int $qrCodeScale = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'active' => $this->active,
            'expires_at' => $this->expires_at?->toISOString(),
            'is_expired' => $this->isExpired(),
            'is_valid' => $this->isValid(),
        ];

        if ($this->includeQrCode) {
            $url = url("/p/{$this->uuid}");
            $data['url'] = $url;
            $data['png_url'] = url("/p/{$this->uuid}.png");
            $data['qr_code'] = app(QrCodeService::class)->toBase64($url, scale: $this->qrCodeScale);
        }

        return $data;
    }
}
