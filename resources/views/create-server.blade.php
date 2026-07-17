<x-filament-panels::page>
    <div x-data="serverCreator()" x-init="init()">

        {{-- Game header --}}
        <div class="relative rounded-xl overflow-hidden mb-6" >
            @if($bannerUrl ?? $egg->icon)
            <img src="{{ $bannerUrl ?? $egg->icon }}" alt="{{ $egg->name }}" style="width:100%; height:215px; object-fit:cover; object-position:center; opacity:0.6;" />
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex items-end p-6">
                <div>
                    <h2 class="text-2xl font-bold text-white">{{ $egg->name }}</h2>
                    <p class="text-gray-300 text-sm mt-1">{{ Str::limit($egg->description, 120) }}</p>
                </div>
            </div>
        </div>
        {{-- Budget and per-node resources --}}
        <div style="display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap;">
            <div class="bg-gray-800 rounded-lg p-3" style="flex:1; min-width:200px;">
                <div class="text-xs text-gray-400 uppercase tracking-wide mb-2 text-center">Budget</div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <div class="text-xs text-gray-500">CPU</div>
                        <div class="text-sm font-semibold" :class="cpuOver ? 'text-red-400' : 'text-green-400'" x-text="budgetCpuText"></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Memory</div>
                        <div class="text-sm font-semibold" :class="memOver ? 'text-red-400' : 'text-green-400'" x-text="budgetMemText"></div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Disk</div>
                        <div class="text-sm font-semibold" :class="diskOver ? 'text-red-400' : 'text-green-400'" x-text="budgetDiskText"></div>
                    </div>
                </div>
            </div>
            @foreach($nodeResources as $nodeId => $res)
                <div class="bg-gray-800 rounded-lg p-3" style="flex:1; min-width:200px;">
                    <div class="text-xs text-gray-400 uppercase tracking-wide mb-2 text-center">{{ $res['name'] }} — Free</div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div>
                            <div class="text-xs text-gray-500">CPU</div>
                            <div class="text-sm font-semibold text-blue-400">{{ $res['free_cpu'] !== null ? $res['free_cpu'] . '%' : '∞' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Memory</div>
                            <div class="text-sm font-semibold text-blue-400">{{ $res['free_memory'] !== null ? $res['free_memory'] . ' MiB' : '∞' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Disk</div>
                            <div class="text-sm font-semibold text-blue-400">{{ $res['free_disk'] !== null ? $res['free_disk'] . ' MiB' : '∞' }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Server name --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-300 mb-2">Server Name</label>
            <input
                type="text"
                x-model="serverName"
                class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:border-blue-500"
                placeholder="My {{ $egg->name }} Server"
            />
        </div>

        {{-- Allocation dropdown --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-300 mb-2">Port / Allocation</label>
            @if($allocations->isEmpty())
                <p class="text-red-400 text-sm">No allocations available. Contact an administrator.</p>
            @else
                <script id="ugsc-allocations" type="application/json">{!! json_encode($allocations->map(fn($a) => ['id' => $a->id, 'label' => $a->ip.':'.$a->port, 'node_id' => $a->node_id])) !!}</script>
                <script id="ugsc-node-resources" type="application/json">{!! json_encode($nodeResources) !!}</script>
                <script id="ugsc-nodes" type="application/json">{!! json_encode($nodes->map(fn($n) => ['id' => $n->id, 'name' => $n->name])) !!}</script>
                <div x-data="{ open: false }">
                    {{-- Node selector --}}
                    <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Node</label>
                    <div class="flex gap-2 mb-4 flex-wrap">
                        @foreach($nodes as $node)
                        <button type="button"
                            @click="nodeId = {{ $node->id }}; allocationId = allocationsByNode({{ $node->id }})[0]?.id || null"
                            :class="nodeId == {{ $node->id }} ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800 text-gray-300 border-gray-600 hover:bg-gray-700'"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors border">
                            {{ $node->name }}
                            <span class="ml-1 text-xs opacity-70">({{ $allocations->where('node_id', $node->id)->count() }} free)</span>
                        </button>
                        @endforeach
                    </div>
                    {{-- Port dropdown --}}
                    <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Port</label>
                    <div class="relative" style="width:300px;">
                        <button type="button"
                            @click="open = !open"
                            class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white text-left flex justify-between items-center focus:outline-none">
                            <span class="text-sm" x-text="allocationsByNode(nodeId).find(a => a.id == allocationId)?.label || 'Select port...'"></span>
                            <svg class="w-4 h-4 flex-shrink-0 ml-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.outside="open = false"
                            class="absolute z-50 mt-1 rounded-lg overflow-y-auto shadow-xl" style="background:oklch(0.141 0.005 285.823); border:1px solid rgba(255,255,255,0.15); width:300px; max-height:240px;">
                            <template x-for="a in allocationsByNode(nodeId)" :key="a.id">
                                <div @click="allocationId = a.id; open = false"
                                    :style="allocationId == a.id ? 'background:#2563eb; color:#fff; padding:8px 16px; cursor:pointer; font-size:0.875rem;' : 'color:#d1d5db; padding:8px 16px; cursor:pointer; font-size:0.875rem;'"
                                    @mouseenter="if(allocationId != a.id) $el.style.background='#374151'"
                                    @mouseleave="if(allocationId != a.id) $el.style.background=''"
                                    x-text="a.label">
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            @endif
        </div>
                {{-- Player count / Slots --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-300 mb-2">
                {{ $settings->slots_mode ? 'Slots' : 'Max Players' }}:
                <span class="text-blue-400 font-bold" x-text="players"></span>
            </label>
            <div class="flex items-center gap-4">
                <input
                    type="range"
                    :min="minPlayers"
                    :max="maxPlayers"
                    x-model="players"
                    @input="recalculate()"
                    class="flex-1"
                />
                <input
                    type="number"
                    :min="minPlayers"
                    :max="maxPlayers"
                    x-model="players"
                    @input="recalculate()"
                    class="w-20 bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-center"
                />
            </div>
        </div>

        {{-- Map size (Rust only) --}}
        @if($showMapSize)
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-300 mb-2">
                Map Size: <span class="text-blue-400 font-bold" x-text="mapSize"></span>
            </label>
            <div class="flex items-center gap-4">
                <input type="range" min="1000" max="6000" step="500" x-model="mapSize" @input="recalculateRust()" class="flex-1" />
                <input type="number" min="1000" max="6000" step="500" x-model="mapSize" @input="recalculateRust()" class="w-24 bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-center" />
            </div>
        </div>
        @endif

        {{-- Resources --}}
        <div style="display:flex; gap:16px; margin-bottom:24px;">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">CPU (%)</label>
                <input
                    type="number"
                    x-model="cpu"
                    @input="checkLimits()"
                    min="1"
                    :class="cpuOver ? 'border-red-500' : 'border-gray-600'"
                    class="w-full bg-gray-800 border rounded-lg px-3 py-2 text-white"
                />
                <p class="text-xs mt-1" :class="cpuOver ? 'text-red-400' : 'text-gray-500'" x-text="cpuHint"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Memory (MiB)</label>
                <input
                    type="number"
                    x-model="memory"
                    @input="checkLimits()"
                    min="1"
                    :class="memOver ? 'border-red-500' : 'border-gray-600'"
                    class="w-full bg-gray-800 border rounded-lg px-3 py-2 text-white"
                />
                <p class="text-xs mt-1" :class="memOver ? 'text-red-400' : 'text-gray-500'" x-text="memHint"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Disk (MiB)</label>
                <input
                    type="number"
                    x-model="disk"
                    @input="checkLimits()"
                    min="1"
                    :class="diskOver ? 'border-red-500' : 'border-gray-600'"
                    class="w-full bg-gray-800 border rounded-lg px-3 py-2 text-white"
                />
                <p class="text-xs mt-1" :class="diskOver ? 'text-red-400' : 'text-gray-500'" x-text="diskHint"></p>
            </div>
        </div>

        {{-- Error message --}}
        <div x-show="errorMessage" class="bg-red-900/50 border border-red-500 rounded-lg p-4 mb-4 text-red-300 text-sm" x-text="errorMessage"></div>

        {{-- Create button --}}
        <button
            @click="createServer()"
            :disabled="cpuOver || memOver || diskOver || creating || !serverName || !allocationId"
            :class="(cpuOver || memOver || diskOver || creating || !serverName || !allocationId) ? 'opacity-40 cursor-not-allowed' : 'hover:bg-blue-700'"
            class="w-full bg-blue-600 text-white font-semibold py-3 rounded-lg transition-colors"
        >
            <span x-show="!creating">Create Server</span>
            <span x-show="creating">Creating...</span>
        </button>

        <button
            @click="window.history.back()"
            class="w-full mt-3 bg-gray-700 text-gray-300 font-medium py-2 rounded-lg hover:bg-gray-600 transition-colors"
        >Back</button>

        {{-- Overallocate confirm modal --}}
        <div x-show="showOverallocateModal" x-cloak
            style="position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:100;">
            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:oklch(0.141 0.005 285.823); border:1px solid rgba(255,255,255,0.15); border-radius:12px; padding:24px; max-width:420px; width:90%;">
                <h3 class="text-white text-base font-semibold mb-2">Confirm overallocation</h3>
                <p class="text-gray-300 text-sm mb-5">This will allocate more CPU or memory than the node currently has reserved for its other servers, though it still has enough raw capacity. If all servers run at full load simultaneously, performance may suffer.</p>
                <div class="flex gap-3">
                    <button @click="showOverallocateModal = false"
                        class="flex-1 bg-gray-700 text-gray-300 font-medium py-2 rounded-lg hover:bg-gray-600 transition-colors">Cancel</button>
                    <button @click="showOverallocateModal = false; createServer(true)"
                        class="flex-1 bg-blue-600 text-white font-medium py-2 rounded-lg hover:bg-blue-700 transition-colors">Create anyway</button>
                </div>
            </div>
        </div>

    </div>

    <script>
    function serverCreator() {
        return {
            serverName: @js($initialName),
            nodeId: {{ $initialNodeId ?? ($nodes->first()?->id ?? 'null') }},
            allocationId: {{ $initialAllocationId ?? ($allocations->first()?->id ?? 'null') }},
            players: {{ $initialPlayers }},
            minPlayers: {{ $settings->min_players }},
            maxPlayers: {{ $settings->max_players }},
            mapSize: {{ $initialMapSize }},
            cpu: {{ $initialCpu }},
            memory: {{ $initialMemory }},
            disk: {{ $initialDisk }},
            cpuLeft: {{ $cpuLeft ?? 'null' }},
            memLeft: {{ $memLeft ?? 'null' }},
            diskLeft: {{ $diskLeft ?? 'null' }},
            cpuOver: false,
            memOver: false,
            diskOver: false,
            cpuHint: '',
            memHint: '',
            diskHint: '',
            budgetCpuText: '',
            budgetMemText: '',
            budgetDiskText: '',
            errorMessage: '',
            showOverallocateModal: false,
            creating: false,
            eggId: {{ $egg->id }},
            ramBase: {{ $settings->ram_base }},
            ramMax: {{ $settings->ram_max }},
            cpuBase: {{ $settings->cpu_base }},
            cpuMax: {{ $settings->cpu_max }},

            init() {
                @if($restored)
                this.checkLimits();
                @else
                this.recalculate();
                this.checkLimits();
                @endif
            },

            recalculate() {
                const p = parseInt(this.players);
                const t = (p - this.minPlayers) / Math.max(1, this.maxPlayers - this.minPlayers);
                const clamp = Math.max(0, Math.min(1, t));
                this.memory = Math.round(this.ramBase + (this.ramMax - this.ramBase) * clamp);
                this.cpu = Math.round(this.cpuBase + ({{ $settings->cpu_max }} - this.cpuBase) * clamp);
                this.checkLimits();
            },

            recalculateRust() {
                const size = parseInt(this.mapSize);
                const extraRam = Math.round((size - 1000) / 1000 * 512);
                this.memory = this.ramBase + extraRam;
                this.checkLimits();
            },

            checkLimits() {
                const cpu = parseInt(this.cpu) || 0;
                const mem = parseInt(this.memory) || 0;
                const disk = parseInt(this.disk) || 0;

                this.cpuOver = this.cpuLeft !== null && cpu > this.cpuLeft;
                this.memOver = this.memLeft !== null && mem > this.memLeft;
                this.diskOver = this.diskLeft !== null && disk > this.diskLeft;

                this.cpuHint = this.cpuLeft !== null ? `${this.cpuLeft}% available` : 'Unlimited';
                this.memHint = this.memLeft !== null ? `${this.memLeft} MiB available` : 'Unlimited';
                this.diskHint = this.diskLeft !== null ? `${this.diskLeft} MiB available` : 'Unlimited';

                this.budgetCpuText = this.cpuLeft !== null ? (this.cpuOver ? `Over limit!` : `${this.cpuLeft}% left`) : 'Unlimited';
                this.budgetMemText = this.memLeft !== null ? (this.memOver ? `Over limit!` : `${this.memLeft} MiB left`) : 'Unlimited';
                this.budgetDiskText = this.diskLeft !== null ? (this.diskOver ? `Over limit!` : `${this.diskLeft} MiB left`) : 'Unlimited';
            },

            allocationsByNode(nodeId) {
                const all = JSON.parse(document.getElementById('ugsc-allocations')?.textContent || '[]');
                return all.filter(a => a.node_id == nodeId);
            },
            createServer(confirmOverallocate = false) {
                if (this.cpuOver || this.memOver || this.diskOver || !this.serverName || !this.allocationId) return;

                const allNodeResources = JSON.parse(document.getElementById('ugsc-node-resources')?.textContent || '{}');
                const res = allNodeResources[this.nodeId];
                if (res) {
                    const cpu = parseInt(this.cpu) || 0;
                    const mem = parseInt(this.memory) || 0;
                    const disk = parseInt(this.disk) || 0;

                    // Hard wall (CPU/Memory): single request vs node's flat total. Always blocked.
                    const overRawHard = (res.raw_cpu > 0 && cpu > res.raw_cpu)
                        || (res.raw_memory > 0 && mem > res.raw_memory);
                    if (overRawHard) {
                        this.errorMessage = 'That node does not have enough physical capacity for this server. Try a smaller configuration or a different node.';
                        return;
                    }

                    // Disk: single request vs free disk (raw - used). Always blocked, no override.
                    // Disk can never be overallocated.
                    const overDiskFree = disk > (res.free_disk ?? Infinity);
                    if (overDiskFree) {
                        this.errorMessage = 'That node does not have enough free disk space for this server. Disk cannot be overallocated. Try a smaller disk size or a different node.';
                        return;
                    }

                    // Soft (CPU/Memory): single request vs free (raw - used). Confirm-to-proceed.
                    const overFreeSoft = (cpu > (res.free_cpu ?? Infinity))
                        || (mem > (res.free_memory ?? Infinity));
                    if (overFreeSoft && !confirmOverallocate) {
                        this.showOverallocateModal = true;
                        return;
                    }
                }

                const params = new URLSearchParams({
                    egg: this.eggId,
                    name: this.serverName,
                    cpu: parseInt(this.cpu),
                    memory: parseInt(this.memory),
                    disk: parseInt(this.disk),
                    allocation_id: parseInt(this.allocationId),
                    node_id: parseInt(this.nodeId),
                    players: parseInt(this.players),
                    map_size: parseInt(this.mapSize),
                });
                window.location.href = '{{ route("filament.app.pages.create-server.variables") }}?' + params.toString();
            }
        }
    }
    </script>
</x-filament-panels::page>
