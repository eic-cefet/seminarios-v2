<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AlertPreferenceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'new_seminar_alert' => ['required', 'boolean'],
            'seminar_type_ids' => ['array'],
            'seminar_type_ids.*' => ['integer', 'exists:seminar_types,id'],
            'subject_ids' => ['array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'seminar_reminder_7d' => ['required', 'boolean'],
            'seminar_reminder_24h' => ['required', 'boolean'],
            'evaluation_prompt' => ['required', 'boolean'],
            'announcements' => ['required', 'boolean'],
            'certificate_ready' => ['required', 'boolean'],
            'seminar_rescheduled' => ['required', 'boolean'],
            'workshop_announcements' => ['required', 'boolean'],
        ];
    }
}
