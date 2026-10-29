<?php

namespace App\Http\Controllers;

use App\Services\ExpiryReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ExpiryController extends Controller
{
    /** GET /api/expiring?days=10 - preview only; sends nothing, marks nothing. */
    public function index(Request $request, ExpiryReminderService $service): JsonResponse
    {
        $days = $this->days($request);
        if ($days === null) {
            return response()->json(['error' => 'days must be an integer between 1 and 365'], 422);
        }

        return response()->json($service->collect($days));
    }

    /** POST /api/run?days=10&dryRun=true&force=true */
    public function run(Request $request, ExpiryReminderService $service): JsonResponse
    {
        $days = $this->days($request);
        if ($days === null) {
            return response()->json(['error' => 'days must be an integer between 1 and 365'], 422);
        }

        try {
            return response()->json(
                $service->run($days, $request->boolean('dryRun'), $request->boolean('force'))
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function days(Request $request): ?int
    {
        $value = $request->query('days');
        if ($value === null) {
            return (int) config('expiry.days');
        }
        if (!ctype_digit((string) $value)) {
            return null;
        }
        $n = (int) $value;

        return ($n >= 1 && $n <= 365) ? $n : null;
    }
}
