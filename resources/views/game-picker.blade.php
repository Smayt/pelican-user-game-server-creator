<x-filament-panels::page>
<style>
.ugsc-grid { display: grid !important; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; width: 100%; }
</style>

<script>
function ugscGamePicker() {
    const data = JSON.parse(document.getElementById("ugsc-eggs").textContent);
    const eggs = data;
    const budgetCpu = "{{ $budgetCpu }}";
    const budgetMemory = "{{ $budgetMemory }}";
    const budgetDisk = "{{ $budgetDisk }}";
    return {
        search: '',
        activeCategory: 'all',
        viewMode: 'grid',
        eggs: eggs,
        filtered: [],
        init() { this.filtered = this.eggs; },
        filterEggs() {
            this.filtered = this.eggs.filter(egg => {
                const matchesSearch = !this.search ||
                    egg.name.toLowerCase().includes(this.search.toLowerCase()) ||
                    (egg.description || '').toLowerCase().includes(this.search.toLowerCase());
                const matchesCategory = this.activeCategory === 'all' ||
                    (this.activeCategory === 'popular' && egg.popular) ||
                    egg.category_slug === this.activeCategory;
                return matchesSearch && matchesCategory;
            });
        },
        selectEgg(egg) {
            window.location.href = '/create-server/configure?egg=' + egg.id;
        }
    }
}
</script>

<div x-data="ugscGamePicker()">

<script id="ugsc-eggs" type="application/json">{!! json_encode($eggs) !!}</script>

<div style="display:flex; gap:16px; margin-bottom:24px;">
    <div class="bg-gray-800 rounded-lg p-4 text-center border border-white">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">CPU Budget</div>
        <div class="text-lg font-semibold text-white">{{ $budgetCpu }}</div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 text-center border border-white">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Memory Budget</div>
        <div class="text-lg font-semibold text-white">{{ $budgetMemory }}</div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 text-center border border-white">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Disk Budget</div>
        <div class="text-lg font-semibold text-white">{{ $budgetDisk }}</div>
    </div>
</div>
<div style="display:flex; gap:16px; margin-bottom:24px;">
    <div class="bg-gray-900 rounded-lg p-4 text-center border border-gray-700">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Free CPU</div>
        <div class="text-lg font-semibold text-blue-400">{{ $totalFreeCpu }}</div>
    </div>
    <div class="bg-gray-900 rounded-lg p-4 text-center border border-gray-700">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Free Memory</div>
        <div class="text-lg font-semibold text-blue-400">{{ $totalFreeMemory }}</div>
    </div>
    <div class="bg-gray-900 rounded-lg p-4 text-center border border-gray-700">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Free Disk</div>
        <div class="text-lg font-semibold text-blue-400">{{ $totalFreeDisk }}</div>
    </div>
    <div class="bg-gray-900 rounded-lg p-4 text-center border border-gray-700">
        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Free Ports</div>
        <div class="text-lg font-semibold text-blue-400">{{ $totalFreePorts }}</div>
    </div>
</div>

<div class="mb-4">
    <input type="text" x-model="search" x-on:input="filterEggs()"
        placeholder="Search for a game..."
        class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:border-blue-500" />
</div>

<div class="flex gap-2 mb-6 flex-wrap">
    <button x-on:click="activeCategory = 'all'; filterEggs()"
        :class="activeCategory === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">All</button>
    @foreach($categories as $cat)
    <button x-on:click="activeCategory = '{{ $cat->slug }}'; filterEggs()"
        :class="activeCategory === '{{ $cat->slug }}' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'"
        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-1.5">
        @if ($cat->icon)
            @svg($cat->icon, 'w-4 h-4')
        @endif
        {{ $cat->name }}
    </button>
    @endforeach
</div>

<div class="flex justify-end mb-4 gap-2">
    <button x-on:click="viewMode = 'grid'"
        :class="viewMode === 'grid' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300'"
        class="px-3 py-2 rounded-lg text-sm transition-colors">Grid</button>
    <button x-on:click="viewMode = 'list'"
        :class="viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300'"
        class="px-3 py-2 rounded-lg text-sm transition-colors">List</button>
</div>

<div :class="viewMode === 'grid' ? 'ugsc-grid' : 'hidden'">
    <template x-for="egg in filtered" :key="egg.id">
        <div x-on:click="selectEgg(egg)"
            class="bg-gray-800 border border-gray-700 rounded-xl overflow-hidden cursor-pointer hover:border-blue-500 transition-all hover:scale-105">
            <div class="h-24 bg-gray-700 overflow-hidden">
                <img :src="egg.icon || '/pelican.svg'" :alt="egg.name"
                    class="w-full h-full object-cover"
                    x-on:error="$el.src='/pelican.svg'" />
            </div>
            <div class="p-2 text-center">
                <div class="text-sm font-medium text-white truncate" x-text="egg.name">Loading...</div>
            </div>
        </div>
    </template>
</div>

<div x-show="viewMode === 'list'" class="flex flex-col gap-2">
    <template x-for="egg in filtered" :key="egg.id">
        <div x-on:click="selectEgg(egg)"
            class="bg-gray-800 border border-gray-700 rounded-xl cursor-pointer hover:border-blue-500 transition-all flex items-center gap-4 p-3">
            <img :src="egg.list_icon || egg.icon || ''" :alt="egg.name"
                class="w-16 h-16 rounded-lg object-cover flex-shrink-0"
                x-show="egg.list_icon || egg.icon" />
            <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-white" x-text="egg.name"></div>
                <div class="text-xs text-gray-400 truncate" x-text="egg.description"></div>
            </div>
        </div>
    </template>
</div>

<div x-show="filtered.length === 0" class="text-center py-12 text-gray-400">
    <p class="text-lg">No games found.</p>
</div>

</div>
</x-filament-panels::page>
