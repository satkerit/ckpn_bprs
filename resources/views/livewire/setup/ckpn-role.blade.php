<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">Setup Role CKPN per Kode Akad</h2>
        <p class="mt-1 text-sm text-surface-500">
            Tentukan kolom mana yang membentuk EAD dan kapan pembiayaan dengan kode akad ini
            ikut masuk perhitungan CKPN — berlaku untuk perhitungan CKPN maupun snapshot seluruh metode PD (Netflow dan Migration).
        </p>
    </div>

    @if (session('status'))
        <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <form wire:submit="simpan" class="card mb-6">
        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-surface-500">
            Tambah / perbarui role
        </h3>

        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="form-label" for="pokpby">Kode Akad (pokpby)</label>
                <select id="pokpby" wire:model="pokpby" class="form-input px-3 py-2.5">
                    <option value="">— pilih kode akad —</option>
                    @foreach ($akads as $akad)
                        <option value="{{ $akad->pokpby }}">{{ $akad->pokpby }} — {{ $akad->nama }}</option>
                    @endforeach
                </select>
                @error('pokpby') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="form-label" for="syarat_masuk">Syarat Masuk CKPN</label>
                <select id="syarat_masuk" wire:model.live="syarat_masuk" class="form-input px-3 py-2.5">
                    @foreach ($syaratOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('syarat_masuk') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <fieldset class="mt-4">
            <legend class="form-label mb-2">Komponen EAD (Eksposur pada Default)</legend>
            <div class="grid gap-3 md:grid-cols-4">
                <label class="flex min-h-11 items-center gap-2 text-sm text-surface-700">
                    <input type="checkbox" wire:model="ead_osmdlc" class="h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                    osmdlc — Sisa Pokok
                </label>
                <label class="flex min-h-11 items-center gap-2 text-sm text-surface-700">
                    <input type="checkbox" wire:model="ead_osmgnc" class="h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                    osmgnc — Sisa Margin
                </label>
                <label class="flex min-h-11 items-center gap-2 text-sm text-surface-700">
                    <input type="checkbox" wire:model="ead_tgkmdl" class="h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                    tgkmdl — Tunggakan Pokok
                </label>
                <label class="flex min-h-11 items-center gap-2 text-sm text-surface-700">
                    <input type="checkbox" wire:model="ead_tgkmgn" class="h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                    tgkmgn — Tunggakan Margin
                </label>
            </div>
            <p class="mt-2 text-xs text-surface-500">
                Centang kolom history yang dijumlahkan untuk EAD akad ini.
                Saat <strong>Syarat Masuk CKPN</strong> = "Masuk bila ada tunggakan pokok",
                <code>tgkmdl</code> wajib dicentang agar EAD terisi.
            </p>
        </fieldset>

        <div class="mt-5 flex justify-end">
            <button type="submit" class="btn btn-primary">Simpan role</button>
        </div>
    </form>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Kode</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Nama</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">EAD</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Syarat Masuk</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-200 bg-white">
                    @forelse ($roles as $role)
                        <tr class="hover:bg-surface-50">
                            <td class="px-5 py-3 font-mono font-semibold text-surface-900">{{ $role->pokpby }}</td>
                            <td class="px-5 py-3 text-surface-700">{{ $role->akad?->nama ?? '—' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-surface-600">{{ implode(' + ', $role->eadColumns()) ?: '—' }}</td>
                            <td class="px-5 py-3 text-surface-700">{{ $role->syarat_masuk->label() }}</td>
                            <td class="px-5 py-3">
                                @can('setup.manage')
                                    <button type="button" wire:click="hapus('{{ $role->pokpby }}')"
                                            wire:confirm="Kembalikan role CKPN akad {{ $role->pokpby }} ke default?"
                                            class="min-h-11 rounded text-danger-600 hover:underline">Reset</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-surface-500">
                            Belum ada role CKPN yang diatur. Jalankan seeder <code>db:seed --class=AkadRoleSeeder</code> untuk isi default.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
