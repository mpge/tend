<?php

use App\Models\Habit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects a future-dated habit toggle', function () {
    $this->travelTo(CarbonImmutable::parse('2026-06-28 10:00'));

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('habits.toggle', $habit), ['date' => '2026-07-15'])
        ->assertSessionHasErrors('date');

    expect($habit->entries()->count())->toBe(0);
});

it('still allows toggling a past or present date', function () {
    $this->travelTo(CarbonImmutable::parse('2026-06-28 10:00'));

    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('habits.toggle', $habit), ['date' => '2026-06-28'])
        ->assertRedirect();

    expect($habit->entries()->count())->toBe(1);
});

it('rejects a non-hex habit color on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), ['name' => 'Walk', 'color' => 'red; drop'])
        ->assertSessionHasErrors('color');

    expect(Habit::query()->count())->toBe(0);
});

it('rejects a non-hex habit color on update', function () {
    $user = User::factory()->create();
    $habit = Habit::factory()->for($user)->create(['color' => '#5fa63d']);

    $this->actingAs($user)
        ->patch(route('habits.update', $habit), ['color' => 'notacolor'])
        ->assertSessionHasErrors('color');

    expect($habit->refresh()->color)->toBe('#5fa63d');
});

it('accepts a valid hex habit color', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.store'), ['name' => 'Walk', 'color' => '#A1B2C3'])
        ->assertRedirect();

    expect(Habit::query()->firstOrFail()->color)->toBe('#A1B2C3');
});

it('rejects an oversized task reorder payload', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tasks.reorder'), ['ids' => range(1, 501)])
        ->assertSessionHasErrors('ids');
});

it('rejects a habit reorder payload with duplicate ids', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('habits.reorder'), ['ids' => [3, 3]])
        ->assertSessionHasErrors('ids.0');
});
