<?php

namespace App\Livewire;

use App\Models\TelegramUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class TicketSearchManager extends Component
{
    public string $fromStationCode = '';

    public string $fromStationName = '';

    public string $toStationCode = '';

    public string $toStationName = '';

    public string $departureDate = '';

    public array $fromStations = [];

    public array $toStations = [];

    /**
     * Refresh the component once the user is authenticated via the API.
     */
    #[On('user-authenticated')]
    public function refreshComponent(): void
    {
        // This will trigger a re-render with the now-authenticated user.
        $this->js('$refresh');
    }

    public function mount(): void
    {
        // The component is mounted before the user is authenticated.
        // We can pre-load some non-user-specific data if necessary.
        $service = app(\App\Services\ETicketService::class);
        $this->fromStations = $service->searchStations('TOSHKENT');
        $this->toStations = $service->searchStations('SAMARQAND');
    }

    #[Computed(persist: true)]
    public function searches()
    {
        /** @var TelegramUser $user */
        if (! Auth::check()) {
            return collect();
        }

        return Auth::user()->activeTicketSearches()->get();
    }

    public function submitSearch(): void
    {
        $this->validate([
            'fromStationCode' => 'required',
            'fromStationName' => 'required',
            'toStationCode' => 'required',
            'toStationName' => 'required',
            'departureDate' => 'required|date',
        ], [
            'fromStationCode.required' => 'Qayerdan jo\'nash bekatini tanlang',
            'fromStationName.required' => 'Qayerdan jo\'nash bekatini tanlang',
            'toStationCode.required' => 'Qayerga borish bekatini tanlang',
            'toStationName.required' => 'Qayerga borish bekatini tanlang',
            'departureDate.required' => 'Jo\'nash sanasini tanlang',
        ]);

        /** @var TelegramUser $user */
        $user = Auth::user();

        if (! $user) {
            $this->dispatch('error', message: 'Foydalanuvchi topilmadi. Iltimos, sahifani yangilang.');

            return;
        }

        $user->ticketSearches()->create([
            'from_station_code' => $this->fromStationCode,
            'from_station_name' => $this->fromStationName,
            'to_station_code' => $this->toStationCode,
            'to_station_name' => $this->toStationName,
            'departure_date' => $this->departureDate,
        ]);

        $this->reset(['fromStationCode', 'fromStationName', 'toStationCode', 'toStationName', 'departureDate']);

        // Force re-evaluation of computed property
        unset($this->searches);

        $this->dispatch('success', message: 'Qidiruv qo\'shildi!');
    }

    public function deleteSearch(int $id): void
    {
        /** @var TelegramUser $user */
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $search = $user->ticketSearches()->find($id);

        if ($search) {
            $search->delete();
            unset($this->searches); // Force re-evaluation
            $this->dispatch('success', message: 'Qidiruv o\'chirildi!');
        }
    }

    public function render()
    {
        return view('livewire.ticket-search-manager');
    }
}
