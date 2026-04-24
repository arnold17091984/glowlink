<div {{ $attributes }} style="position: relative;">
    {{ $getChildComponentContainer() }}
    <x-filament::modal width="2xl" id="select-layout" :close-by-clicking-away="true">
        <x-slot name="heading">
            Select Layout
        </x-slot>
        <x-slot name="trigger" style="width: 100%; position: absolute; top: 0">
            <x-filament::button icon="heroicon-o-squares-2x2" style="width: 100%">
                Select Layout
            </x-filament::button>
        </x-slot>
        <div>
            <x-filament::tabs style="display:flex;" x-data="{ activeTab: $wire.$entangle('selectedTab') }">
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center">
                    @for ($i = 1; $i <= 12; $i++)
                        <x-filament::tabs.item style="flex: 25%; max-width: 25%;"
                            alpine-active="activeTab === {{$i}}"
                            x-on:click="activeTab =  {{$i}}">
                            <img src="{{ asset('layout/richmessage/layout-' . $i . '.svg') }}" alt="Layout {{ $i }}"
                            x-bind:style="activeTab == {{$i}} ? 'border: 2px solid currentColor;' : 'border: 2px solid transparent'">
                        </x-filament::tabs.item>
                    @endfor
                </div>
            </x-filament::tabs>
        </div>
        <x-slot name="footer">
            <x-filament::button wire:click="onClickSelectedLayout('{{ $getStatePath() }}')" x-on:click="$dispatch('close-modal', { id: 'select-layout' })">
                Apply
            </x-filament::button>
            <x-filament::button color="gray" wire:click="onClickClose('{{ $getStatePath() }}')" x-on:click="$dispatch('close-modal', { id: 'select-layout' })">
                Close
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
