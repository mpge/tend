<?php

use App\Enums\TaskBucket;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('redirects guests away from the tasks page', function () {
    $this->get(route('tasks.index'))->assertRedirect(route('login'));
});

it('shows the signed-in user their open tasks split into lanes', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['bucket' => TaskBucket::Important, 'title' => 'Pay rent']);
    Task::factory()->for($user)->eventual()->create(['title' => 'Plan trip']);

    // Another user's task must never leak in.
    Task::factory()->create(['title' => 'Someone else']);

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks')
            ->has('important', 1)
            ->has('eventual', 1)
            ->where('important.0.title', 'Pay rent')
            ->where('eventual.0.title', 'Plan trip')
        );
});

it('creates a task in the important lane by default', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tasks.store'), ['title' => 'Buy groceries'])
        ->assertRedirect();

    $task = Task::query()->firstOrFail();
    expect($task->title)->toBe('Buy groceries')
        ->and($task->user_id)->toBe($user->id)
        ->and($task->bucket)->toBe(TaskBucket::Important)
        ->and($task->status)->toBe(TaskStatus::Open);
});

it('can create a task directly in the eventual lane', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('tasks.store'), [
        'title' => 'Someday',
        'bucket' => 'eventual',
    ])->assertRedirect();

    expect(Task::query()->firstOrFail()->bucket)->toBe(TaskBucket::Eventual);
});

it('requires a title to create a task', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tasks.store'), ['title' => ''])
        ->assertSessionHasErrors('title');

    expect(Task::query()->count())->toBe(0);
});

it('lets a user edit their task and move it between lanes', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['title' => 'Old', 'bucket' => TaskBucket::Important]);

    $this->actingAs($user)->patch(route('tasks.update', $task), [
        'title' => 'New',
        'bucket' => 'eventual',
        'priority' => 2,
    ])->assertRedirect();

    $task->refresh();
    expect($task->title)->toBe('New')
        ->and($task->bucket)->toBe(TaskBucket::Eventual)
        ->and($task->priority)->toBe(2);
});

it('marks a task done and stamps completed_at', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['status' => TaskStatus::Open]);

    $this->actingAs($user)->patch(route('tasks.update', $task), ['status' => 'done'])->assertRedirect();

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Done)
        ->and($task->completed_at)->not->toBeNull();
});

it('reopening a done task clears completed_at', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->done()->create();

    $this->actingAs($user)->patch(route('tasks.update', $task), ['status' => 'open'])->assertRedirect();

    $task->refresh();
    expect($task->status)->toBe(TaskStatus::Open)
        ->and($task->completed_at)->toBeNull();
});

it('forbids editing a task that belongs to someone else', function () {
    $task = Task::factory()->create(['title' => 'theirs']);

    $this->actingAs(User::factory()->create())
        ->patch(route('tasks.update', $task), ['title' => 'hacked'])
        ->assertForbidden();

    expect($task->refresh()->title)->toBe('theirs');
});

it('lets a user delete their task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $this->actingAs($user)->delete(route('tasks.destroy', $task))->assertRedirect();

    expect(Task::query()->count())->toBe(0);
});

it('forbids deleting a task that belongs to someone else', function () {
    $task = Task::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('tasks.destroy', $task))
        ->assertForbidden();

    expect(Task::query()->count())->toBe(1);
});

it('reorders tasks within a lane and ignores ids the user does not own', function () {
    $user = User::factory()->create();
    $a = Task::factory()->for($user)->create(['position' => 0]);
    $b = Task::factory()->for($user)->create(['position' => 1]);
    $foreign = Task::factory()->create(['position' => 5]);

    $this->actingAs($user)->post(route('tasks.reorder'), [
        'ids' => [$b->id, $a->id, $foreign->id],
    ])->assertRedirect();

    expect($a->refresh()->position)->toBe(1)
        ->and($b->refresh()->position)->toBe(0)
        ->and($foreign->refresh()->position)->toBe(5);
});
