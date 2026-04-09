<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Get the number of items per page from the request, with a maximum limit.
     */
    protected function getPerPage(Request $request, int $default = 10, int $max = 50): int
    {
        return max(1, min((int) $request->query('per_page', $default), $max));
    }
}
