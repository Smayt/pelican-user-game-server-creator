@php
    $currentValue = $getState();
    $curatedIcons = require base_path('plugins/user-game-server-creator/src/Support/CuratedTablerIcons.php');
@endphp
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if ($currentValue)
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="width:28px; height:28px; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid var(--gray-600, #444); border-radius:6px; padding:4px;">
                @svg($currentValue, 'w-5 h-5')
            </span>
            <p style="font-size:11px; color:var(--gray-400, #888); margin:0;">
                Currently selected: <strong>{{ $currentValue }}</strong>
                @if (! in_array(str_replace('tabler-', '', $currentValue), $curatedIcons))
                    (not in curated set)
                @endif
            </p>
        </div>
    @endif
</x-dynamic-component>
