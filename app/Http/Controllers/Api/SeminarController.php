<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SeminarResource;
use App\Models\Seminar;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SeminarController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Seminar::query()
            ->with(['seminarType', 'subjects', 'speakers.speakerData'])
            ->withCount('registrations')
            ->active();

        // Filter by seminar type
        if ($request->has('type')) {
            $query->whereHas('seminarType', function ($q) use ($request) {
                $q->where('name', $request->input('type'));
            });
        }

        // Filter by subject
        if ($request->has('subject')) {
            $query->whereHas('subjects', function ($q) use ($request) {
                $q->where('subjects.id', $request->input('subject'));
            });
        }

        // Filter upcoming only
        if ($request->boolean('upcoming')) {
            $query->upcoming();
        }

        // Filter expired only
        if ($request->boolean('expired')) {
            $query->expired();
        }

        // Sorting
        $sortField = $request->input('sort', 'scheduled_at');
        $sortDirection = $request->input('direction', 'asc');

        if ($sortField === 'scheduled_at') {
            $query->orderBy('scheduled_at', $sortDirection);
        }

        $seminars = $query->paginate($this->getPerPage($request, 15));

        return SeminarResource::collection($seminars);
    }

    public function upcoming(): AnonymousResourceCollection
    {
        $seminars = Seminar::query()
            ->with(['seminarType', 'subjects', 'speakers.speakerData'])
            ->withCount('registrations')
            ->active()
            ->upcoming()
            ->orderBy('scheduled_at', 'asc')
            ->limit(6)
            ->get();

        return SeminarResource::collection($seminars);
    }

    public function show(string $slug): SeminarResource
    {
        $seminar = Seminar::query()
            ->with(['seminarType', 'subjects', 'speakers.speakerData', 'workshop', 'seminarLocation'])
            ->withCount('registrations')
            ->withAvg('ratings', 'score')
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

        // Filter upcoming only
        if ($request->boolean('upcoming')) {
            $query->upcoming();
        }

        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy('scheduled_at', $sortDirection);

        $seminars = $query->paginate($this->getPerPage($request, 15));

        return SeminarResource::collection($seminars);
    }
}
