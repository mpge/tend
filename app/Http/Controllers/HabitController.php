<?php

namespace App\Http\Controllers;

use App\Enums\HabitCadence;
use App\Http\Requests\StoreHabitRequest;
use App\Http\Requests\UpdateHabitRequest;
use App\Models\Habit;
use App\Models\HabitEntry;
use App\Models\User;
use App\Support\StreakCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HabitController extends Controller
{
    public function __construct(private readonly StreakCalculator $streaks) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        \assert($user instanceof User);

        $today = CarbonImmutable::now();

        $habits = $user->habits()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->with('entries')
            ->get()
            ->map(fn (Habit $habit): array => $this->present($habit, $today))
            ->all();

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
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach (array_values($validated['ids']) as $position => $id) {
            $user->habits()->whereKey($id)->update(['position' => $position]);
        }

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Habit $habit, CarbonImmutable $today): array
    {
        $dates = $habit->entries
            ->map(fn (HabitEntry $entry): string => $entry->entry_date->toDateString())
            ->all();

        $gridStart = $today->subDays(139)->toDateString();
        $recent = array_values(array_filter($dates, fn (string $date): bool => $date >= $gridStart));

        return [
            'id' => $habit->id,
            'name' => $habit->name,
            'cadence' => $habit->cadence->value,
            'target_per_period' => $habit->target_per_period,
            'color' => $habit->color,
            'icon' => $habit->icon,
            'position' => $habit->position,
            'current_streak' => $this->streaks->current($dates, $today, $habit->cadence),
            'longest_streak' => $this->streaks->longest($dates, $habit->cadence),
            'done_today' => $this->doneThisPeriod($dates, $today, $habit->cadence),
            'entries' => $recent,
        ];
    }

    /**
     * @param  array<int, string>  $dates
     */
    private function doneThisPeriod(array $dates, CarbonImmutable $today, HabitCadence $cadence): bool
    {
        return match ($cadence) {
            HabitCadence::Daily => in_array($today->toDateString(), $dates, true),
            HabitCadence::Weekly => collect($dates)->contains(
                fn (string $date): bool => CarbonImmutable::parse($date)->startOfWeek()->equalTo($today->startOfWeek()),
            ),
        };
    }
}
