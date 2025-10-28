<?php

use App\Livewire\Settings\Tokens as TokensComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('creates a token and exposes the plain text value once', function () {
    $user = User::factory()->create();

    actingAs($user);

    $component = Livewire::test(TokensComponent::class)
        ->set('tokenName', 'Deploy Token')
        ->call('createToken')
        ->assertSet('tokenName', '');

    $plainText = $component->get('plainTextToken');

    expect($plainText)->not->toBeNull();
    expect(strlen($plainText))->toBeGreaterThan(20);

    $token = $user->tokens()->where('name', 'Deploy Token')->first();
    expect($token)->not->toBeNull();

    $component->call('resetPlainTextToken');
    expect($component->get('plainTextToken'))->toBeNull();
});

it('can revoke a token', function () {
    $user = User::factory()->create();
    actingAs($user);

    $token = $user->createToken('CLI Script')->accessToken;

    Livewire::test(TokensComponent::class)
        ->call('revokeToken', $token->id);

    expect($user->tokens()->whereKey($token->id)->exists())->toBeFalse();
});

it('renders the settings page', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->get('/settings');

    $response->assertOk()
        ->assertSeeLivewire(TokensComponent::class);
});
