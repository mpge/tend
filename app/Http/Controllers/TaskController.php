<?php

namespace App\Http\Controllers;

use App\Enums\TaskBucket;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Models\User;
use App\Support\TaskPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct(private readonly TaskPresenter $presenter) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        \assert($user instanceof User);

        return Inertia::render('Tasks', [
            'important' => $this->lane($user, TaskBucket::Important),
            'eventual' => $this->lane($user, TaskBucket::Eventual),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $user = $request->user();
        \assert($user instanceof User);

        $data = $request->validated();
        $bucket = $data['bucket'] ?? TaskBucket::Important->value;

        $position = (int) $user->tasks()->where('bucket', $bucket)->max('position') + 1;

        $user->tasks()->create([
            'title' => $data['title'],
            'notes' => $data['notes'] ?? null,
            'bucket' => $bucket,
            'priority' => $data['priority'] ?? 0,
            'due_date' => $data['due_date'] ?? null,
            'position' => $position,
        ]);

        return back();
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $data = $request->validated();

        if (array_key_exists('status', $data)) {
            $task->completed_at = $data['status'] === TaskStatus::Done->value ? now() : null;
        }

        $task->fill($data)->save();

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

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
                $user->tasks()->whereKey($id)->update(['position' => $position]);
            }
        });

        return back();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lane(User $user, TaskBucket $bucket): array
    {
        return $this->presenter->collection(
            $user->tasks()
                ->where('bucket', $bucket)
                ->where('status', TaskStatus::Open)
                ->orderBy('position')
                ->orderBy('id')
                ->get()
        );
    }
}
