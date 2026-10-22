<x-layouts::error :title="__('Page not found')">
    <div class="flex w-full max-w-lg flex-col items-center gap-4 text-center">
        <flux:icon.question-mark-circle class="size-12 text-zinc-400" />

        <flux:heading level="1" size="xl">{{ __('Page not found') }}</flux:heading>

        <flux:text class="max-w-md">
            {{ __('The page you were looking for does not exist or has been moved.') }}
        </flux:text>

        @auth
            <flux:button :href="route('dashboard')" variant="primary"> {{ __('Back to the dashboard') }} </flux:button>
        @else
            <flux:button :href="route('login')" variant="primary"> {{ __('Log in') }} </flux:button>
        @endauth
    </div>
</x-layouts::error>
