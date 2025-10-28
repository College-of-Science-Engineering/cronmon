<?php

namespace App\Livewire\Settings;

use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Tokens extends Component
{
    #[Validate('required|string|max:255')]
    public string $tokenName = '';

    public ?string $plainTextToken = null;

    public function createToken(): void
    {
        $this->validate();

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $newToken = $user->createToken($this->tokenName);

        $this->plainTextToken = $newToken->plainTextToken;
        $this->tokenName = '';

        $this->dispatch('token-created');
    }

    public function resetPlainTextToken(): void
    {
        $this->plainTextToken = null;
    }

    public function revokeToken(int $tokenId): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        /** @var PersonalAccessToken $token */
        $token = $user->tokens()->whereKey($tokenId)->firstOrFail();
        $token->delete();

        $this->dispatch('token-revoked');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $tokens = $user->tokens()
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.settings.tokens', [
            'tokens' => $tokens,
        ]);
    }
}
