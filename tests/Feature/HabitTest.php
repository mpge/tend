<?php

use App\Enums\HabitCadence;
use App\Models\Habit;
use App\Models\HabitEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('redirects guests away from the habits page', function () {
    $this->get(route('habits.index'))->assertRedirect(route('login'));
});

it('shows the user their active habits with streak data', function () {
    $this->travelTo(CarbonImmutable::parse('2026-06-28 10:00'));

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create(['name' => 'Exercise']);
    foreach (['2026-06-26', '2026-06-27', '2026-06-28'] as $date) {
        HabitEntry::factory()->for($habit)->on($date)->create();
    }

    Habit::factory()->for($user)->inactive()->create(['name' => 'Hidden']);
    Habit::factory()->create(['name' => 'Someone else']);

    $this->actingAs($user)
        ->get(route('habits.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Habits')
            ->has('habits', 1)
            ->where('habits.0.name', 'Exercise')
            ->where('habits.0.current_streak', 3)
            ->where('habits.0.done_today', true)
        );
});

it('creates a daily habit by default', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), ['name' => 'Read'])
        ->assertRedirect();

    $habit = Habit::query()->firstOrFail();
    expect($habit->name)->toBe('Read')
        ->and($habit->user_id)->toBe($user->id)
        ->and($habit->cadence)->toBe(HabitCadence::Daily)
        ->and($habit->is_active)->toBeTrue();
});

it('requires a name to create a habit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), ['name' => ''])
        ->assertSessionHasErrors('name');

    expect(Habit::query()->count())->toBe(0);
});

it('updates a habit', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create(['name' => 'Old', 'color' => '#f97316']);

    $this->actingAs($user)->patch(route('habits.update', $habit), [
        'name' => 'New',
        'cadence' => 'weekly',
        'color' => '#06b6d4',
    ])->assertRedirect();

    $habit->refresh();
    expect($habit->name)->toBe('New')
        ->and($habit->cadence)->toBe(HabitCadence::Weekly)
        ->and($habit->color)->toBe('#06b6d4');
});

it('forbids updating a habit owned by someone else', function () {
    $habit = Habit::factory()->create(['name' => 'theirs']);

    $this->actingAs(User::factory()->create())
        ->patch(route('habits.update', $habit), ['name' => 'hacked'])
        ->assertForbidden();

    expect($habit->refresh()->name)->toBe('theirs');
});

it('deletes a habit', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)->delete(route('habits.destroy', $habit))->assertRedirect();

    expect(Habit::query()->count())->toBe(0);
});

it('toggles a habit entry on and off for today', function () {
    $this->travelTo(CarbonImmutable::parse('2026-06-28 10:00'));

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();

    // First toggle creates today's entry.
    $this->actingAs($user)->post(route('habits.toggle', $habit))->assertRedirect();
    expect($habit->entries()->count())->toBe(1)
        ->and($habit->entries()->first()->entry_date->toDateString())->toBe('2026-06-28');

    // Second toggle removes it.
    $this->actingAs($user)->post(route('habits.toggle', $habit))->assertRedirect();
    expect($habit->entries()->count())->toBe(0);
});

it('toggles a habit entry for a specific date', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('habits.toggle', $habit), ['date' => '2026-06-20'])
        ->assertRedirect();

    expect($habit->entries()->first()->entry_date->toDateString())->toBe('2026-06-20');
});

it('forbids toggling a habit owned by someone else', function () {
    $habit = Habit::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('habits.toggle', $habit))
        ->assertForbidden();

    expect($habit->entries()->count())->toBe(0);
});

it('reorders habits and ignores ids the user does not own', function () {
    $user = User::factory()->create();
    $a = Habit::factory()->for($user)->create(['position' => 0]);
    $b = Habit::factory()->for($user)->create(['position' => 1]);
    $foreign = Habit::factory()->create(['position' => 5]);

    $this->actingAs($user)->post(route('habits.reorder'), [
        'ids' => [$b->id, $a->id, $foreign->id],
    ])->assertRedirect();

    expect($a->refresh()->position)->toBe(1)
        ->and($b->refresh()->position)->toBe(0)
        ->and($foreign->refresh()->position)->toBe(5);
});
