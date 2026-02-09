<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SeminarResource;
use App\Http\Resources\WorkshopResource;
use App\Models\Workshop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkshopController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $workshops = Workshop::query()
            ->whereHas('seminars')
            ->withCount('seminars')
            ->orderByDesc('created_at')
            ->get();

        return WorkshopResource::collection($workshops);
    }

    public function show(Workshop $workshop): WorkshopResource
    {
        $workshop->load(['seminars' => function ($query) {
            $query->with(['seminarType', 'speakers.speakerData'])
                ->withCount('registrations')
                ->where('active', true)
                ->orderBy('scheduled_at', 'asc');
        }]);

        return new WorkshopResource($workshop);
    }

    public function seminars(Workshop $workshop, Request $request): AnonymousResourceCollection
    {
        $query = $workshop->seminars()
            ->with(['seminarType', 'speakers.speakerData', 'subjects'])
            ->withCount('registrations')
            ->where('active', true);

        // Filter upcoming only
        if ($request->boolean('upcoming')) {
            $query->where('scheduled_at', '>=', now());
        }

        $sortDirection = $request->input('direction', 'asc');
        $query->orderBy('scheduled_at', $sortDirection);

        $seminars = $query->paginate($this->getPerPage($request, 15));

        return SeminarResource::collection($seminars);
    }
}
