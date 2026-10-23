<?php

use App\Models\ProductClass;
use App\Rules\ProductClassRules;
use App\Services\ProductClassService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit class')] class extends Component
{
    #[Locked]
    public ProductClass $productClass;

    public string $name = '';

    /**
     * Mount the component.
     */
    public function mount(ProductClass $productClass): void
    {
        Gate::authorize('update', $productClass);

        $this->productClass = $productClass;
        $this->name = (string) $productClass->name;
    }

    /**
     * Store the changes.
     */
    public function save(ProductClassService $productClasses): void
    {
        Gate::authorize('update', $this->productClass);

        $validated = $this->validate(ProductClassRules::details($this->productClass));

        $productClasses->update($this->productClass, $validated);

        $this->redirect(route('product-classes.index'), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Edit class') }}</flux:heading>
        <flux:text class="mt-1">{{ $productClass->name }}</flux:text>
    </header>

    <form wire:submit="save" class="max-w-3xl space-y-8">
        <flux:card>
            <flux:heading size="lg">{{ __('Class information') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" :error="$errors->first('name')" />
        </flux:card>

        <div class="flex justify-end gap-3">
            <flux:button :href="route('product-classes.index')" variant="ghost"> {{ __('Cancel') }} </flux:button>

            <flux:button variant="primary" type="submit"> {{ __('Save') }} </flux:button>
        </div>
    </form>
</div>
