<div class="space-y-8">
    {{-- New Search Form --}}
    <div class="rounded-lg p-6" style="background-color: var(--tg-theme-secondary-bg-color, #f4f4f5);">
        <h2 class="text-xl font-semibold mb-5" style="color: var(--tg-theme-text-color, #212529);">Yangi Qidiruv</h2>

        <form wire:submit="submitSearch" class="space-y-4">
            {{-- From Station --}}
            <div>
                <label class="block text-sm font-medium mb-2" style="color: var(--tg-theme-text-color, #212529);">Qayerdan</label>
                <livewire:station-select
                    wire:key="from-station"
                    placeholder="Masalan: Toshkent"
                    x-on:station-selected="$wire.set('fromStationCode', $event.detail.value); $wire.set('fromStationName', $event.detail.label)"
                />
                @error('fromStationCode') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- To Station --}}
            <div>
                <label class="block text-sm font-medium mt-4 mb-2" style="color: var(--tg-theme-text-color, #212529);">Qayerga</label>
                <livewire:station-select
                    wire:key="to-station"
                    placeholder="Masalan: Samarqand"
                    x-on:station-selected="$wire.set('toStationCode', $event.detail.value); $wire.set('toStationName', $event.detail.label)"
                />
                @error('toStationCode') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Departure Date --}}
            <div>
                <label for="date" class="block text-sm font-medium mt-4 mb-2" style="color: var(--tg-theme-text-color, #212529);">Jo'nash sanasi</label>
                <input
                    type="date"
                    id="date"
                    wire:model.lazy="departureDate"
                    min="{{ now()->format('Y-m-d') }}"
                    class="w-full px-4 py-2.5 border-none rounded-lg focus:ring-2 focus:ring-blue-500"
                    style="background-color: var(--tg-theme-bg-color, #ffffff); color: var(--tg-theme-text-color, #212529);"
                >
                @error('departureDate') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Submit Button --}}
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full font-semibold py-3 px-4 rounded-lg transition-transform transform active:scale-95 disabled:opacity-70"
                style="background-color: var(--tg-theme-button-color, #2481cc); color: var(--tg-theme-button-text-color, #ffffff);"
            >
                <span wire:loading.remove>Qidiruvga qo'shish</span>
                <span wire:loading>Bajarilmoqda...</span>
            </button>
        </form>
    </div>

    {{-- My Searches List --}}
    <div class="rounded-lg p-6" style="background-color: var(--tg-theme-secondary-bg-color, #f4f4f5);">
        <h2 class="text-xl font-semibold mb-4" style="color: var(--tg-theme-text-color, #212529);">Mening qidiruvlarim</h2>

{{--        @if ($this->searches->isEmpty())--}}
{{--            <p class="text-center py-8" style="color: var(--tg-theme-hint-color, #999999);">Hali qidiruvlar yo'q</p>--}}
{{--        @else--}}
{{--            <div class="space-y-3">--}}
{{--                @foreach ($this->searches as $search)--}}
{{--                    <div wire:key="{{ $search->id }}" class="rounded-lg p-4 transition-shadow duration-200" style="background-color: var(--tg-theme-bg-color, #ffffff);">--}}
{{--                        <div class="flex justify-between items-center">--}}
{{--                            <div>--}}
{{--                                <p class="font-semibold" style="color: var(--tg-theme-text-color, #212529);">--}}
{{--                                    {{ $search->from_station_name }} → {{ $search->to_station_name }}--}}
{{--                                </p>--}}
{{--                                <p class="text-sm mt-1" style="color: var(--tg-theme-hint-color, #999999);">--}}
{{--                                    <span class="font-mono">📅</span> {{ $search->departure_date->format('d.m.Y') }}--}}
{{--                                </p>--}}
{{--                            </div>--}}
{{--                            <button--}}
{{--                                wire:click="deleteSearch({{ $search->id }})"--}}
{{--                                wire:confirm="Bu qidiruvni o'chirishni xohlaysizmi?"--}}
{{--                                class="p-2 rounded-full hover:bg-red-500/10 text-red-500 transition-colors"--}}
{{--                                aria-label="O'chirish"--}}
{{--                            >--}}
{{--                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>--}}
{{--                            </button>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                @endforeach--}}
{{--            </div>--}}
{{--        @endif--}}
    </div>
</div>
