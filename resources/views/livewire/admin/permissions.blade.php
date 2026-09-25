<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">Permission</h2>
        <p class="mt-1 text-sm text-surface-500">Kelola permission aplikasi secara terpusat.</p>
    </div>
    @if (session('status'))
        <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @can('permission.manage')
        <form wire:submit="simpan" class="card mb-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label class="form-label" for="permission-name">Nama permission</label>
                    <input id="permission-name" wire:model="name" placeholder="contoh: laporan.view" class="form-input px-3 py-2.5">
                    @error('name') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ $editingId ? 'Perbarui' : 'Simpan' }}</button>
                    @if ($editingId)
                        <button type="button" wire:click="batalEdit" class="btn btn-secondary">Batal</button>
                    @endif
                </div>
            </div>
        </form>
    @endcan

    <div class="card overflow-hidden p-0">
        <table class="min-w-full text-sm">
            <thead class="bg-surface-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Permission</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Jumlah Role</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-200 bg-white">
                @forelse ($permissions as $permission)
                    <tr class="hover:bg-surface-50">
                        <td class="px-5 py-3 font-mono font-semibold text-surface-900">{{ $permission->name }}</td>
                        <td class="px-5 py-3 text-surface-600">{{ $permission->roles_count }}</td>
                        <td class="px-5 py-3">
                            <button wire:click="edit({{ $permission->id }})" class="min-h-11 rounded text-primary-700 hover:underline">Edit</button>
                            @if ($permission->name !== 'permission.manage')
                                <button wire:click="hapus({{ $permission->id }})" wire:confirm="Hapus permission ini?" class="ml-4 min-h-11 rounded text-danger-600 hover:underline">Hapus</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-8 text-center text-surface-500">Belum ada permission.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
