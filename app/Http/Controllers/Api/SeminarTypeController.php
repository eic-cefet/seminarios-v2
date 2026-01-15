<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SeminarTypeResource;
use App\Models\SeminarType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SeminarTypeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $types = SeminarType::query()
            ->orderBy('name')
            ->get();

        return SeminarTypeResource::collection($types);
    }
}
