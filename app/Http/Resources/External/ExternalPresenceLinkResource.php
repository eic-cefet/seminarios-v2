<?php

namespace App\Http\Resources\External;

use App\Http\Resources\Concerns\FormatsDates;
use App\Models\PresenceLink;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PresenceLink */
class ExternalPresenceLinkResource extends JsonResource
{
    use FormatsDates;

    public function __construct(
        PresenceLink $resource,
        protected bool $includeQrCode = false,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $url = url("/p/{$this->uuid}");
        $payload = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'active' => $this->active,
            'expires_at' => $this->formatDate($this->expires_at),
            'is_expired' => $this->isExpired(),
            'is_valid' => $this->isValid(),
            'url' => $url,
            'png_url' => url("/p/{$this->uuid}.png"),
        ];

        if ($this->includeQrCode) {
            $payload['qr_code'] = app(QrCodeService::class)->toBase64($url, scale: 20);
        }

        return $payload;
    }
}
