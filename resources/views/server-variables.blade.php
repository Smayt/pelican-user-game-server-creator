<x-filament-panels::page>
    <div x-data="serverVariables()" x-init="init()">

        <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
            <h2 class="text-xl font-bold text-white">{{ $egg->name }} — Server variables</h2>
            <div class="flex items-center gap-4 text-xs text-gray-400">
                <span>{{ $name }}</span>
                <span class="text-gray-600">|</span>
                <span>{{ $cpu }}% CPU</span>
                <span class="text-gray-600">|</span>
                <span>{{ $memory }} MiB</span>
                <span class="text-gray-600">|</span>
                <span>{{ $disk }} MiB</span>
                <span class="text-gray-600">|</span>
                <span>{{ $allocationLabel }}</span>
            </div>
        </div>

        @if(count($editableVariables) === 0)
            <p class="text-gray-400 text-sm mb-6">No additional settings needed for this game.</p>
        @else
            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($editableVariables as $var)
                    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-200">
                                {{ $var['name'] }}
                                @if($var['required'])<span class="text-red-400">*</span>@endif
                            </label>
                            <span class="text-xs font-mono text-gray-500 bg-gray-900 border border-gray-700 rounded px-2 py-0.5">{{ $var['pill'] }}</span>
                        </div>
                        @if($var['fallback'])
                            <p class="text-xs text-amber-400 mb-2">No admin default set — a value is required.</p>
                        @endif
                        <input
                            type="text"
                            x-model="vars['{{ $var['env_variable'] }}']"
                            @input="checkValid()"
                            class="w-full bg-gray-900 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 text-sm"
                            placeholder="{{ $var['default'] }}"
                        />
                        @if($var['description'])
                            <p class="text-xs text-gray-500 mt-2">{{ $var['description'] }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div x-show="errorMessage" class="bg-red-900/50 border border-red-500 rounded-lg p-4 mb-4 text-red-300 text-sm" x-text="errorMessage"></div>

        <button
            @click="deploy()"
            :disabled="!valid || deploying"
            :class="(!valid || deploying) ? 'opacity-40 cursor-not-allowed' : 'hover:bg-blue-700'"
            class="w-full bg-blue-600 text-white font-semibold py-3 rounded-lg transition-colors"
        >
            <span x-show="!deploying">Deploy</span>
            <span x-show="deploying">Deploying...</span>
        </button>

        <button
            @click="window.history.back()"
            class="w-full mt-3 bg-gray-700 text-gray-300 font-medium py-2 rounded-lg hover:bg-gray-600 transition-colors"
        >Back</button>

    </div>

    <script>
    function serverVariables() {
        return {
            vars: {
                @foreach($editableVariables as $var)
                '{{ $var['env_variable'] }}': @js($var['default']),
                @endforeach
            },
            requiredKeys: @js(collect($editableVariables)->where('required', true)->pluck('env_variable')->values()),
            valid: true,
            deploying: false,
            errorMessage: '',
            eggId: {{ $egg->id }},
            name: @js($name),
            cpu: {{ $cpu }},
            memory: {{ $memory }},
            disk: {{ $disk }},
            allocationId: {{ $allocationId }},

            init() {
                this.checkValid();
            },

            checkValid() {
                this.valid = this.requiredKeys.every(k => (this.vars[k] ?? '').toString().trim().length > 0);
            },

            deploy() {
                if (!this.valid) return;
                this.deploying = true;
                this.errorMessage = '';

                fetch('{{ route("ugsc.create-server") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        name: this.name,
                        egg_id: this.eggId,
                        cpu: this.cpu,
                        memory: this.memory,
                        disk: this.disk,
                        allocation_id: this.allocationId,
                        confirm_overallocate: true,
                        variables: this.vars,
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        this.errorMessage = data.message || 'Failed to create server.';
                        this.deploying = false;
                    }
                })
                .catch(() => {
                    this.errorMessage = 'An error occurred. Please try again.';
                    this.deploying = false;
                });
            }
        }
    }
    </script>
</x-filament-panels::page>
