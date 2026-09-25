<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">Role &amp; Permission</h2>
        <p class="mt-1 text-sm text-surface-500">Buat dan kelola permission role.</p>
    </div>
    @if (session('status'))
        <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @can('role.manage')
        <form wire:submit="simpan" class="card mb-6">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row">
                <div class="flex-1">
                    <label class="form-label" for="role-name">Nama role</label>
                    <input id="role-name" wire:model="name" placeholder="Nama role" class="form-input px-3 py-2.5">
                    @error('name') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-primary">{{ $editingId ? 'Perbarui Role' : 'Simpan Role' }}</button>
                    @if ($editingId)
                        <button type="button" wire:click="batalEdit" class="btn btn-secondary">Batal</button>
                    @endif
                </div>
            </div>

            <fieldset>
                <legend class="form-label">Permission</legend>
                <div class="grid max-h-64 gap-2 overflow-y-auto rounded-lg border border-surface-200 bg-surface-50 p-4 md:grid-cols-3">
                    @foreach ($permissions as $permission)
                        <label class="flex min-h-11 items-center gap-2 rounded px-2 text-sm text-surface-700 hover:bg-white">
                            <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->id }}"
                                   class="h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                            {{ $permission->name }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </form>
    @endcan

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Role</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Permission</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-200 bg-white">
                    @forelse ($roles as $role)
                        <tr class="hover:bg-surface-50">
                            <td class="px-5 py-3 font-semibold text-surface-900">{{ $role->name }}</td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($role->permissions as $permission)
                                        <span class="rounded bg-surface-100 px-2 py-0.5 text-xs font-medium text-surface-600">{{ $permission->name }}</span>
                                    @empty
                                        <span class="text-surface-500">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <button wire:click="edit({{ $role->id }})" class="min-h-11 rounded text-primary-700 hover:underline">Edit</button>
                                @if ($role->name !== 'Administrator')
                                    <button wire:click="hapus({{ $role->id }})" wire:confirm="Hapus role ini?" class="ml-4 min-h-11 rounded text-danger-600 hover:underline">Hapus</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-surface-500">Belum ada role.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
