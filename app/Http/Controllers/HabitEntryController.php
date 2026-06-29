<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HabitEntryController extends Controller
{
    /**
     * Toggle a habit's completion for a given day (defaults to today).
     */
    public function toggle(Request $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('update', $habit);

        /** @var array{date?: string|null} $validated */
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $date = isset($validated['date'])
            ? CarbonImmutable::parse($validated['date'])->toDateString()
            : CarbonImmutable::now()->toDateString();

        $existing = $habit->entries()->whereDate('entry_date', $date)->first();

        if ($existing !== null) {
            $existing->delete();
        } else {
            $habit->entries()->create(['entry_date' => $date]);
        }

        return back();
    }
}
