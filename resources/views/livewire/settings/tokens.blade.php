@php
    use Illuminate\Support\Str;
@endphp

<div>
    <div class="mb-6">
        <flux:heading size="xl">Settings</flux:heading>
        <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
            Manage personal access tokens for the Admin API.
        </flux:text>
    </div>

    @if($plainTextToken)
        <flux:callout icon="key" class="mb-6">
            <flux:heading size="lg">New token created</flux:heading>
            <flux:text class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">
                Copy this token now. For security reasons it will not be shown again once hidden.
            </flux:text>
            <div class="mt-4">
                <flux:input readonly value="{{ $plainTextToken }}" class="font-mono text-sm" />
            </div>
            <flux:button wire:click="resetPlainTextToken" variant="ghost" class="mt-4">
                Hide token
            </flux:button>
        </flux:callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <flux:card>
            <flux:heading size="lg">Create a new token</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                Give each token a descriptive name so you can revoke it later without guessing.
            </flux:text>

            <form wire:submit.prevent="createToken" class="mt-6 space-y-4">
                <flux:field>
                    <flux:label for="token-name">Token name</flux:label>
                    <flux:input id="token-name" wire:model.live="tokenName" placeholder="e.g. Production deploy script" />
                    <flux:error for="tokenName" />
                </flux:field>

                <flux:button type="submit" icon="key" class="cursor-pointer">
                    Generate token
                </flux:button>
            </form>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">How tokens work</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                Use tokens to authenticate requests to the Admin API. Keep them secret and rotate them regularly.
            </flux:text>
            <flux:text class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">
                When a token is revoked, any scripts using it will immediately receive 401 responses.
            </flux:text>
        </flux:card>
    </div>

    <flux:card class="mt-6">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Active tokens</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $tokens->count() }} {{ Str::plural('token', $tokens->count()) }}
            </flux:text>
        </div>

        @if($tokens->isEmpty())
            <div class="text-center py-12">
                <flux:icon.key class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="md" class="mt-4">No tokens yet</flux:heading>
                <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                    Generate a token to start integrating with the Admin API.
                </flux:text>
            </div>
        @else
            <flux:table class="mt-6">
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Created</flux:table.column>
                    <flux:table.column>Last used</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($tokens as $token)
                        <flux:table.row :key="$token->id">
                            <flux:table.cell>
                                <flux:text class="font-medium">{{ $token->name }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $token->created_at?->diffForHumans() ?? '—' }}
                                </flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $token->last_used_at?->diffForHumans() ?? 'Never' }}
                                </flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center justify-end">
                                    <flux:button
                                        wire:click="revokeToken({{ $token->id }})"
                                        wire:confirm="Revoke this token? Integrations using it will lose access immediately."
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                    >
                                        Revoke
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
