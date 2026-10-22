<x-layouts::error :title="__('Access denied')">
    <div class="flex w-full max-w-lg flex-col items-center gap-4 text-center">
        <flux:icon.shield-exclamation class="size-12 text-amber-500" />

        <flux:heading level="1" size="xl">{{ __('Access denied') }}</flux:heading>

        <flux:text class="max-w-md">
            {{ __('You do not have permission to open this page. Contact an administrator if you believe this is a mistake.') }}
        </flux:text>

        @auth
            <flux:button :href="route('dashboard')" variant="primary"> {{ __('Back to the dashboard') }} </flux:button>
        @else
            <flux:button :href="route('login')" variant="primary"> {{ __('Log in') }} </flux:button>
        @endauth
    </div>
</x-layouts::error>
