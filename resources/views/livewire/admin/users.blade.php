<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">Manajemen User</h2>
        <p class="mt-1 text-sm text-surface-500">Kelola akun dan role pengguna.</p>
    </div>
    @if (session('status'))
        <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @can('user.manage')
        <form wire:submit="simpan" class="card mb-6">
            <div class="grid gap-4 md:grid-cols-5">
                <div>
                    <label class="form-label" for="name">Nama</label>
                    <input id="name" wire:model="name" placeholder="Nama" class="form-input px-3 py-2.5">
                    @error('name') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="email">Email</label>
                    <input id="email" wire:model="email" type="email" placeholder="Email" class="form-input px-3 py-2.5">
                    @error('email') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="password">Password</label>
                    <input id="password" wire:model="password" type="password" placeholder="Password" class="form-input px-3 py-2.5">
                    @error('password') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="role">Role</label>
                    <select id="role" wire:model="role" class="form-input px-3 py-2.5">
                        <option value="">Pilih role</option>
                        @foreach ($roles as $item)
                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn btn-primary w-full">Tambah</button>
                </div>
            </div>
        </form>
    @endcan

    <div class="card overflow-hidden p-0">
        <table class="min-w-full text-sm">
            <thead class="bg-surface-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Nama</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Email</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Role</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-200 bg-white">
                @forelse ($users as $user)
                    <tr class="hover:bg-surface-50">
                        <td class="px-5 py-3 font-medium text-surface-900">{{ $user->name }}</td>
                        <td class="px-5 py-3 text-surface-600">{{ $user->email }}</td>
                        <td class="px-5 py-3 text-surface-600">{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td>
                        <td class="px-5 py-3">
                            @if ($user->id !== auth()->id())
                                <button wire:click="hapus({{ $user->id }})" wire:confirm="Hapus user ini?" class="min-h-11 rounded text-danger-600 hover:underline">Hapus</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-surface-500">Belum ada user.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
