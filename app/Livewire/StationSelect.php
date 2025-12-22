<?php

namespace App\Livewire;

use App\Services\ETicketService;
use Livewire\Component;

class StationSelect extends Component
{
    public $search = '';

    public $label = ''; // Stansiya nomi (foydalanuvchi ko'radigan)

    public $value = ''; // Stansiya kodi (wire:model uchun)

    public $placeholder = '';

    public $options = [];

    public $showDropdown = false;

    // Ushbu maydon wire:model bilan bog'lanishi uchun kerak
    protected $listeners = ['valueUpdated' => '$refresh'];

    public function updatedSearch()
    {
        if (strlen($this->search) < 2) {
            $this->options = [];

            return;
        }

        $service = app(ETicketService::class);
        $stations = $service->searchStations($this->search);

        // Railway API dan kelgan formatni birxillashtiramiz
        $this->options = $stations;

        $this->showDropdown = true;
    }

    public function selectOption($value, $label)
    {
        $this->value = $value;
        $this->label = $label;
        $this->search = $label;
        $this->showDropdown = false;

        $this->dispatch('stationSelected', [
            'value' => $value,
            'label' => $label,
            'type' => $this->placeholder, // yoki boshqa identifikator
        ]);
    }

    public function render()
    {
        return view('livewire.station-select');
    }
}
