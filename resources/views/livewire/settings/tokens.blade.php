@php
    use Illuminate\Support\Str;
@endphp

<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
        <div>
            <flux:heading size="xl">Settings</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                Manage personal access tokens for the Admin API.
            </flux:text>
        </div>
        <flux:button
            wire:click="openCreateModal"
            icon="key"
            variant="primary"
            class="sm:mt-0 sm:self-start"
        >
            Generate token
        </flux:button>
    </div>

    <flux:card>
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

    <flux:modal variant="flyout" wire:model="showCreateModal">
        <div class="space-y-6">
            @if($plainTextToken)
                <div>
                    <flux:heading size="lg">Token created</flux:heading>
                    <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                        Copy this token now. For security reasons it will not be shown again after closing.
                    </flux:text>
                </div>

                <flux:input icon="key" value="{{ $plainTextToken }}" readonly copyable class="font-mono text-sm" />

                <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                    Store the token in a secure password manager. Revoke it if it becomes exposed.
                </flux:text>

                <flux:button
                    variant="primary"
                    icon="check"
                    class="cursor-pointer"
                    wire:click="$set('showCreateModal', false)"
                >
                    Done
                </flux:button>
            @else
                <div>
                    <flux:heading size="lg">Generate a new token</flux:heading>
                    <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                        Give each token a descriptive name so you know which integrations are using it.
                    </flux:text>
                </div>

                <form wire:submit.prevent="createToken" class="space-y-4">
                    <flux:field>
                        <flux:label for="token-name">Token name</flux:label>
                        <flux:input.group>
                            <flux:input
                                id="token-name"
                                wire:model.live="tokenName"
                                placeholder="e.g. Production deploy"
                                class="w-full"
                            />
                            <flux:button type="submit" icon="key" class="cursor-pointer">
                                Create token
                            </flux:button>
                        </flux:input.group>
                        <flux:error for="tokenName" />
                    </flux:field>

                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                        Tokens authenticate requests via the `Authorization: Bearer` header. You can revoke them at any time.
                    </flux:text>
                </form>
            @endif
        </div>
    </flux:modal>
</div>
