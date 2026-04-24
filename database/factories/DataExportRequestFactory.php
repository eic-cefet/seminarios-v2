<?php

namespace Database\Factories;

use App\Models\DataExportRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataExportRequest>
 */
class DataExportRequestFactory extends Factory
{
    protected $model = DataExportRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => DataExportRequest::STATUS_QUEUED,
        ];
    }
}
