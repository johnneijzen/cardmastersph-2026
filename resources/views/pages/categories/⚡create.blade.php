<?php

use App\Models\Category;
use App\Rules\CategoryRules;
use App\Services\CategoryService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add category')] class extends Component
{
    public string $name = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        Gate::authorize('create', Category::class);
    }

    /**
     * Store the category.
     */
    public function save(CategoryService $categories): void
    {
        Gate::authorize('create', Category::class);

        $validated = $this->validate(CategoryRules::details());

        $categories->create($validated);

        $this->redirect(route('categories.index'), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Add category') }}</flux:heading>
        <flux:text class="mt-1">{{ __('A group that the inventory items are classified in.') }}</flux:text>
    </header>

    <form wire:submit="save" class="max-w-3xl space-y-8">
        <flux:card>
            <flux:heading size="lg">{{ __('Category information') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" :error="$errors->first('name')" />
        </flux:card>

        <div class="flex justify-end gap-3">
            <flux:button :href="route('categories.index')" variant="ghost"> {{ __('Cancel') }} </flux:button>

            <flux:button variant="primary" type="submit"> {{ __('Save') }} </flux:button>
        </div>
    </form>
</div>
