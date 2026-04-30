<?php

namespace App\Http\Resources\External;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class ExternalResourceCollection extends ResourceCollection
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
        if (! $this->resource instanceof AbstractPaginator) {
            return [];
        }

        return [
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'from' => $this->resource->firstItem(),
                'to' => $this->resource->lastItem(),
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
