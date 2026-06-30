@php
    $statePath = $getStatePath();
    $curatedIcons = require base_path('plugins/user-game-server-creator/src/Support/CuratedTablerIcons.php');
    $allIcons = require base_path('plugins/user-game-server-creator/src/Support/TablerIconList.php');
    $currentValue = $getState();
@endphp
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            search: '',
            curated: @js($curatedIcons),
        }"
        class="fi-ugsc-icon-picker"
    >
        <x-filament::input.wrapper class="fi-ugsc-icon-picker-search">
            <input
                type="text"
                x-model.debounce.200ms="search"
                placeholder="Search icons or browse below..."
                class="fi-input"
            />
        </x-filament::input.wrapper>

        <div
            style="display:grid; grid-template-columns:repeat(auto-fill, minmax(64px, 1fr)); gap:8px; margin-top:12px; max-height:480px; min-height:0; overflow-y:auto; padding:4px; contain:layout;"
        >
            @foreach ($curatedIcons as $iconName)
                @php
                    $fullName = "tabler-{$iconName}";
                @endphp
                <label
                    x-show="search === '' || '{{ $iconName }}'.includes(search.toLowerCase().replace(/ /g, '-'))"
                    style="position:relative; display:flex; flex-direction:column; align-items:center; gap:4px; padding:8px 4px; border-radius:8px; cursor:pointer; border:1px solid transparent;"
                    :style="$el.querySelector('input').checked ? 'border-color: rgb(59 130 246); background: rgba(59,130,246,0.1);' : ''"
                    title="{{ $iconName }}"
                >
                    <input
                        type="radio"
                        name="{{ $statePath }}"
                        value="{{ $fullName }}"
                        wire:model="{{ $statePath }}"
                        style="position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; pointer-events:none;"
                    />

                    <span style="width:24px; height:24px; display:flex; align-items:center; justify-content:center;">
                        @svg($fullName, 'w-6 h-6')
                    </span>
                    <span style="font-size:10px; color:var(--gray-400, #888); text-align:center; line-height:1.1; word-break:break-word;">
                        {{ Str::limit($iconName, 10) }}
                    </span>
                </label>
            @endforeach
        </div>

        <p style="font-size:11px; color:var(--gray-400, #888); margin-top:8px;">
            Showing {{ count($curatedIcons) }} curated icons.
        </p>
    </div>
</x-dynamic-component>
