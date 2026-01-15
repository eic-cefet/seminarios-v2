<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Subject::query()
            ->withCount('seminars');

        // Sort by seminar count or name
        if ($request->input('sort') === 'seminars') {
            $query->orderByDesc('seminars_count');
        } else {
            $query->orderBy('name');
        }

        // Limit results
        if ($limit = $request->input('limit')) {
            $query->limit((int) $limit);
        }

        return SubjectResource::collection($query->get());
    }

    public function show(Subject $subject): SubjectResource
    {
        $subject->loadCount('seminars');

        return new SubjectResource($subject);
    }
}
