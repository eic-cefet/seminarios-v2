<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\BugReportRequest;
use App\Mail\BugReportMail;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class BugReportController extends Controller
{
    public function store(BugReportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var array<int, \Illuminate\Http\UploadedFile> $files */
        $files = $request->file('files', []);

        Mail::to(config('mail.bug_report_email'))
            ->send(new BugReportMail(
                reportSubject: $validated['subject'],
                message: $validated['message'],
                reporterName: $validated['name'] ?? null,
                reporterEmail: $validated['email'] ?? null,
                files: $files,
            ));

        AuditLog::record(AuditEvent::BugReportSubmitted, eventData: [
            'subject' => $validated['subject'],
        ]);

        return response()->json([
            'message' => 'Bug report enviado com sucesso!',
        ]);
    }
}
