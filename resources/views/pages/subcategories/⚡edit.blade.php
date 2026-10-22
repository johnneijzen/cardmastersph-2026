<?php

use App\Models\Category;
use App\Models\Subcategory;
use App\Rules\SubcategoryRules;
use App\Services\SubcategoryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit subcategory')] class extends Component
{
    #[Locked]
    public Subcategory $subcategory;

    public string $name = '';

    public string $categories_id = '';

    /**
     * Mount the component.
     */
    public function mount(Subcategory $subcategory): void
    {
        Gate::authorize('update', $subcategory);

        $this->subcategory = $subcategory;
        $this->name = (string) $subcategory->name;
        $this->categories_id = (string) ($subcategory->categories_id ?? '');
    }

    /**
     * The categories a subcategory can belong to.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    /**
     * Store the changes.
     */
    public function save(SubcategoryService $subcategories): void
    {
        Gate::authorize('update', $this->subcategory);

        $validated = $this->validate(SubcategoryRules::details($this->categories_id, $this->subcategory));

        $subcategories->update($this->subcategory, $validated);

        $this->redirect(route('subcategories.index'), navigate: true);
    }
};
?>

<div class="flex h-full w-full flex-1 flex-col gap-6">
    <header>
        <flux:heading level="1" size="xl">{{ __('Edit subcategory') }}</flux:heading>
        <flux:text class="mt-1">{{ $subcategory->name }}</flux:text>
    </header>

    <form wire:submit="save" class="max-w-3xl space-y-8">
        <flux:card>
            <flux:heading size="lg">{{ __('Subcategory information') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Name')" :error="$errors->first('name')" />

                <flux:select wire:model="categories_id" :label="__('Category')" :error="$errors->first('categories_id')">
                    <option value="">{{ __('Choose a category') }}</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        <div class="flex justify-end gap-3">
            <flux:button :href="route('subcategories.index')" variant="ghost"> {{ __('Cancel') }} </flux:button>

            <flux:button variant="primary" type="submit"> {{ __('Save') }} </flux:button>
        </div>
    </form>
</div>
