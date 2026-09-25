<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">Master Kode Akad</h2>
        <p class="mt-1 text-sm text-surface-500">
            Daftar kode akad yang dipakai produk pembiayaan, pembiayaan, dan history pembiayaan.
        </p>
    </div>

    @if (session('status'))
        <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @error('hapus')
        <x-alert type="danger" class="mb-4">{{ $message }}</x-alert>
    @enderror

    @can('setup.manage')
        <form wire:submit="simpan" class="card mb-6">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-surface-500">
                {{ $editing ? 'Ubah kode akad '.$editing : 'Tambah kode akad' }}
            </h3>
            <div class="grid gap-4 md:grid-cols-5">
                <div>
                    <label class="form-label" for="pokpby">Kode Akad</label>
                    <input id="pokpby" wire:model="pokpby" placeholder="06" maxlength="10"
                           @readonly($editing !== null)
                           class="form-input px-3 py-2.5 {{ $editing ? 'bg-surface-100 text-surface-600' : '' }}">
                    @error('pokpby') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                    @if ($editing)
                        <p class="mt-1 text-xs text-surface-500">Kode tidak dapat diubah karena dipakai sebagai referensi.</p>
                    @endif
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="nama">Nama Akad</label>
                    <input id="nama" wire:model="nama" placeholder="Murabahah" class="form-input px-3 py-2.5">
                    @error('nama') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="skema">Skema</label>
                    <select id="skema" wire:model="skema" class="form-input px-3 py-2.5">
                        @foreach ($skemaOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('skema') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end gap-3">
                    <label class="flex min-h-11 items-center gap-2 text-sm text-surface-700">
                        <input type="checkbox" wire:model="aktif" class="h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                        Aktif
                    </label>
                    <button type="submit" class="btn btn-primary flex-1">{{ $editing ? 'Simpan' : 'Tambah' }}</button>
                </div>
            </div>
            @if ($editing)
                <button type="button" wire:click="batal" class="mt-3 text-sm font-semibold text-surface-600 hover:text-surface-900">
                    Batal edit
                </button>
            @endif
        </form>
    @endcan

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Kode</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Nama Akad</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Skema</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">Produk</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">Pembiayaan</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">History</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-200 bg-white">
                    @forelse ($akads as $akad)
                        <tr class="hover:bg-surface-50">
                            <td class="px-5 py-3 font-mono font-semibold text-surface-900">{{ $akad->pokpby }}</td>
                            <td class="px-5 py-3 text-surface-700">{{ $akad->nama }}</td>
                            <td class="px-5 py-3 text-surface-600">{{ $skemaOptions[$akad->skema] ?? $akad->skema }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $akad->aktif ? 'bg-success-50 text-success-700' : 'bg-surface-100 text-surface-600' }}">
                                    {{ $akad->aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums text-surface-600">{{ $akad->produk_count }}</td>
                            <td class="px-5 py-3 text-right tabular-nums text-surface-600">{{ $akad->pembiayaan_count }}</td>
                            <td class="px-5 py-3 text-right tabular-nums text-surface-600">{{ $akad->history_count }}</td>
                            <td class="whitespace-nowrap px-5 py-3">
                                @can('setup.manage')
                                    <button type="button" wire:click="edit('{{ $akad->pokpby }}')" class="min-h-11 rounded text-primary-700 hover:underline">Ubah</button>
                                    <button type="button" wire:click="hapus('{{ $akad->pokpby }}')"
                                            wire:confirm="Hapus kode akad {{ $akad->pokpby }} — {{ $akad->nama }}?"
                                            class="ml-3 min-h-11 rounded text-danger-600 hover:underline">Hapus</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-8 text-center text-surface-500">Belum ada kode akad. Jalankan seeder referensi atau tambahkan manual.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
