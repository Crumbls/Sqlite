<x-filament::page>
    <div>
        <form wire:submit="update">
            {{ $this->form }}
            <br />
            <x-filament::button
                type="submit"
                size="lg"
            >
                {{ __('sqlite::sqlite.actions.save_changes') }}
            </x-filament::button>
        </form>

        <x-filament-actions::modals />
    </div>
</x-filament::page>
