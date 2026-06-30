<x-filament-panels::page>
    <div x-data="permissionsManager()">
        <div class="mb-6">
            <h2 class="text-xl font-bold text-white">{{ $limit->user->username }}</h2>
            <p class="text-gray-400 text-sm">Manage which servers this user can see and delete.</p>
        </div>

        <div x-show="message" class="bg-green-900/50 border border-green-500 rounded-lg p-4 mb-4 text-green-300 text-sm" x-text="message"></div>

        @if($servers->isEmpty())
            <p class="text-gray-400">No other servers exist to grant access to.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-400 border-b border-gray-700">
                            <th class="py-2 pr-4">Server</th>
                            <th class="py-2 px-4 text-center">Can See</th>
                            <th class="py-2 px-4 text-center">Can Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($servers as $server)
                        <tr class="border-b border-gray-800">
                            <td class="py-3 pr-4 text-white">{{ $server->name }}</td>
                            <td class="py-3 px-4 text-center">
                                <input type="checkbox"
                                    x-model="visible"
                                    value="{{ $server->id }}"
                                    @change="if (!visible.includes('{{ $server->id }}')) deletable = deletable.filter(id => id !== '{{ $server->id }}')"
                                    class="w-5 h-5" />
                            </td>
                            <td class="py-3 px-4 text-center">
                                <input type="checkbox"
                                    x-model="deletable"
                                    value="{{ $server->id }}"
                                    :disabled="!visible.includes('{{ $server->id }}')"
                                    :class="!visible.includes('{{ $server->id }}') ? 'opacity-30' : ''"
                                    class="w-5 h-5" />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <button
                @click="save()"
                :disabled="saving"
                class="mt-6 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
            >
                <span x-show="!saving">Save Changes</span>
                <span x-show="saving">Saving...</span>
            </button>
        @endif
    </div>

    <script>
    function permissionsManager() {
        return {
            visible: {!! json_encode(array_map('strval', $visibleServerIds)) !!},
            deletable: {!! json_encode(array_map('strval', $deletableServerIds)) !!},
            saving: false,
            message: '',

            save() {
                this.saving = true;
                this.message = '';
                fetch('{{ route("ugsc.save-permissions") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        limit_id: {{ $limit->id }},
                        visible: this.visible,
                        deletable: this.deletable,
                    })
                })
                .then(r => r.json())
                .then(data => {
                    this.saving = false;
                    if (data.success) {
                        this.message = 'Saved successfully.';
                        setTimeout(() => this.message = '', 3000);
                    }
                });
            }
        }
    }
    </script>
</x-filament-panels::page>
