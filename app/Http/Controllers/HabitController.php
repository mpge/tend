<?php

namespace App\Http\Controllers;

use App\Enums\HabitCadence;
use App\Http\Requests\StoreHabitRequest;
use App\Http\Requests\UpdateHabitRequest;
use App\Models\Habit;
use App\Models\User;
use App\Support\HabitPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HabitController extends Controller
{
    public function __construct(private readonly HabitPresenter $presenter) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        \assert($user instanceof User);

        $today = CarbonImmutable::now();

        $habits = $this->presenter->collection(
            $user->habits()
                ->where('is_active', true)
                ->orderBy('position')
                ->orderBy('id')
                ->with('entries')
                ->get(),
            $today,
        );

        return Inertia::render('Habits', [
            'habits' => $habits,
            'today' => $today->toDateString(),
        ]);
    }

    public function store(StoreHabitRequest $request): RedirectResponse
    {
        $user = $request->user();
        \assert($user instanceof User);

        $data = $request->validated();

        $position = (int) $user->habits()->max('position') + 1;

        $user->habits()->create([
            'name' => $data['name'],
            'cadence' => $data['cadence'] ?? HabitCadence::Daily->value,
            'target_per_period' => $data['target_per_period'] ?? 1,
            'color' => $data['color'] ?? '#f97316',
            'icon' => $data['icon'] ?? null,
            'position' => $position,
        ]);

        return back();
    }

    public function update(UpdateHabitRequest $request, Habit $habit): RedirectResponse
    {
        Gate::authorize('update', $habit);

        $habit->fill($request->validated())->save();

        return back();
    }

    public function destroy(Habit $habit): RedirectResponse
    {
        Gate::authorize('delete', $habit);

        $habit->delete();

        return back();
    }

    public function reorder(Request $request): RedirectResponse
    {
        $user = $request->user();
        \assert($user instanceof User);

        /** @var array{ids: array<int, int>} $validated */
        $validated = $request->validate([
            'ids' => ['required', 'array', 'max:500'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        DB::transaction(function () use ($user, $validated): void {
            foreach (array_values($validated['ids']) as $position => $id) {
                $user->habits()->whereKey($id)->update(['position' => $position]);
            }
        });

        return back();
    }
}
