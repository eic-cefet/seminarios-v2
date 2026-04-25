<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SeminarResource;
use App\Models\Seminar;
use App\Models\Subject;
use App\Services\SeminarQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SeminarController extends Controller
{
    public function index(Request $request, SeminarQueryService $seminars): AnonymousResourceCollection
    {
        $query = $seminars->forList(Seminar::query())->active();

        if ($request->has('type')) {
            $query->whereHas('seminarType', function ($q) use ($request) {
                $q->where('name', $request->input('type'));
            });
        }

        if ($request->has('subject')) {
            $query->whereHas('subjects', function ($q) use ($request) {
                $q->where('subjects.id', $request->input('subject'));
            });
        }

        if ($request->boolean('upcoming')) {
            $query->upcoming();
        }

        if ($request->boolean('expired')) {
            $query->expired();
        }

        $sortField = $request->input('sort', 'scheduled_at');
        $sortDirection = in_array($request->input('direction'), ['asc', 'desc']) ? $request->input('direction') : 'asc';

        if ($sortField === 'scheduled_at') {
            $query->orderBy('scheduled_at', $sortDirection);
        }

        $seminars = $query->paginate($this->getPerPage($request, 15));

        return SeminarResource::collection($seminars);
    }

    public function upcoming(SeminarQueryService $seminars): AnonymousResourceCollection
    {
        $upcoming = $seminars->forList(Seminar::query())
            ->active()
            ->upcoming()
            ->orderBy('scheduled_at', 'asc')
            ->limit(6)
            ->get();

        return SeminarResource::collection($upcoming);
    }

    public function show(string $slug, SeminarQueryService $seminars): SeminarResource
    {
        $seminar = $seminars->forDetail(Seminar::query())
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        return new SeminarResource($seminar);
    }

    public function bySubject(Subject $subject, Request $request): AnonymousResourceCollection
    {
        $query = $subject->seminars()
            ->with(['seminarType', 'speakers.speakerData'])
            ->withCount('registrations')
            ->active();

        if ($request->boolean('upcoming')) {
            $query->upcoming();
        }

        $sortDirection = in_array($request->input('direction'), ['asc', 'desc']) ? $request->input('direction') : 'desc';
        $query->orderBy('scheduled_at', $sortDirection);

        $seminars = $query->paginate($this->getPerPage($request, 15));

        return SeminarResource::collection($seminars);
    }
}
