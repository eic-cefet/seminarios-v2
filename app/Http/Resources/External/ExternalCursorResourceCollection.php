<?php

namespace App\Http\Resources\External;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\CursorPaginator;

class ExternalCursorResourceCollection extends ResourceCollection
{
    public function __construct(mixed $resource, ?string $collects = null)
    {
        if ($collects !== null) {
            $this->collects = $collects;
        }
        parent::__construct($resource);
    }

    /**
     * @return array<int, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        if (! $this->resource instanceof CursorPaginator) {
            return [];
        }

        return [
            'meta' => [
                'per_page' => $this->resource->perPage(),
                'next_cursor' => $this->resource->nextCursor()?->encode(),
                'prev_cursor' => $this->resource->previousCursor()?->encode(),
                'has_more' => $this->resource->hasMorePages(),
            ],
        ];
    }

    /**
     * Strip the default `links` block; we override the envelope entirely in `with()`.
     *
     * @param  array<string, mixed>  $paginated
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [];
    }
}
