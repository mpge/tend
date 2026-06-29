<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets unverified users into the app when verification is not required', function () {
    config(['app.require_email_verification' => false]);

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

it('redirects unverified users to verification when it is required', function () {
    config(['app.require_email_verification' => true]);

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

it('lets verified users into the app when verification is required', function () {
    config(['app.require_email_verification' => true]);

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});
