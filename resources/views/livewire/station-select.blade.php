<div class="relative w-full" x-data x-on:click.away="$wire.showDropdown = false">
    <div class="relative">
        <input
            type="text"
            wire:model.live.debounce.500ms="search"
            placeholder="{{ $placeholder }}"
            class="w-full px-4 py-2.5 border-none rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition"
            style="background-color: var(--tg-theme-bg-color, #ffffff); color: var(--tg-theme-text-color, #212529);"
            x-on:focus="$wire.showDropdown = true"
        >

        {{-- Loading Spinner --}}
        <div wire:loading wire:target="search" class="absolute inset-y-0 right-0 flex items-center pr-3">
            <svg class="animate-spin h-5 w-5" style="color: var(--tg-theme-link-color, #2481cc);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>

    {{-- Dropdown --}}
    @if($showDropdown && !empty($options))
        <div
            class="absolute z-10 w-full mt-1 shadow-lg max-h-60 overflow-y-auto rounded-lg"
            style="background-color: var(--tg-theme-secondary-bg-color, #f4f4f5);"
        >
            <div class="py-1">
                @foreach($options as $option)
                    <div
                        wire:click="selectOption('{{ $option['value'] }}', {{ Illuminate\Support\Js::from($option['label']) }})"
                        class="px-4 py-2 text-sm cursor-pointer hover:bg-gray-100/50"
                        style="color: var(--tg-theme-text-color, #212529);"
                    >
                        {{ $option['label'] }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
