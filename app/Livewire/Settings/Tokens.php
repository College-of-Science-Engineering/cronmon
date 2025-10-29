<?php

namespace App\Livewire\Settings;

use App\Events\SomethingNoteworthyHappened;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Tokens extends Component
{
    #[Validate('required|string|max:255')]
    public string $tokenName = '';

    public ?string $plainTextToken = null;

    public bool $showCreateModal = false;

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->tokenName = '';
        $this->plainTextToken = null;
        $this->showCreateModal = true;
    }

    public function updatedShowCreateModal(bool $isOpen): void
    {
        if (! $isOpen) {
            $this->resetValidation();
            $this->reset(['tokenName', 'plainTextToken']);
        }
    }

    public function createToken(): void
    {
        $this->validate();

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $tokenName = $this->tokenName;
        $newToken = $user->createToken($tokenName);

        SomethingNoteworthyHappened::dispatch("{$user->full_name} created personal access token {$tokenName}");

        $this->plainTextToken = $newToken->plainTextToken;
        $this->tokenName = '';
        $this->showCreateModal = true;

    }

    public function revokeToken(int $tokenId): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        /** @var PersonalAccessToken $token */
        $token = $user->tokens()->whereKey($tokenId)->firstOrFail();
        $tokenName = $token->name;
        $token->delete();

        SomethingNoteworthyHappened::dispatch("{$user->full_name} revoked personal access token {$tokenName}");
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
