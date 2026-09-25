<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">Klasifikasi CKPN</h2>
        <p class="mt-1 text-sm text-surface-500">
            CKPN Individual hanya TOP N rekening NPF (kolektibilitas 3–5) dengan outstanding terbesar.
            NPF di luar TOP N dan kolektibilitas 1–2 masuk CKPN Kolektif.
        </p>
    </div>

    @if (session('status'))
        <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @can('klasifikasi.manage')
        <form wire:submit="simpanParameter" class="card mb-4">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="form-label" for="maksRekening">Jumlah maksimal rekening individual</label>
                    <input id="maksRekening" type="number" min="1" wire:model="maksRekening" class="form-input w-40 px-3 py-2.5">
                    @error('maksRekening') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn btn-secondary">Simpan parameter</button>
            </div>
        </form>
    @endcan

    <form wire:submit="klasifikasikan" class="card mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="form-label" for="periode">Periode</label>
                <select id="periode" wire:model.live="periode" class="form-input px-3 py-2.5">
                    <option value="">— pilih periode —</option>
                    @foreach ($periodes as $item)
                        <option value="{{ $item->periode }}">{{ $item->periode }} ({{ $item->status->label() }})</option>
                    @endforeach
                </select>
                @error('periode') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
            </div>
            @can('klasifikasi.manage')
                <button type="submit" wire:confirm="Hitung ulang klasifikasi periode ini?" class="btn btn-primary">
                    Klasifikasikan
                </button>
            @endcan
            <p class="text-sm text-surface-500">
                Individual: {{ $ringkasan['individual'] ?? 0 }} · Kolektif: {{ $ringkasan['kolektif'] ?? 0 }}
            </p>
        </div>
    </form>

    <div class="mb-4">
        <input wire:model.live.debounce.400ms="cari" placeholder="Cari nomor kontrak" class="form-input max-w-xs px-3 py-2.5">
    </div>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Kontrak</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Tipe</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Alasan</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">Nilai individual</th>
                        @can('klasifikasi.manage')
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Aksi</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-200 bg-white">
                    @forelse ($klasifikasi as $baris)
                        <tr class="hover:bg-surface-50">
                            <td class="px-5 py-3 font-mono text-surface-900">{{ $baris->nokontrak }}</td>
                            <td class="px-5 py-3">{{ $baris->tipe->label() }}</td>
                            <td class="px-5 py-3 text-surface-600">{{ $baris->alasan }}</td>
                            <td class="px-5 py-3 text-right font-mono text-surface-700">{{ number_format((float) $baris->nilai_individual, 2, ',', '.') }}</td>
                            @can('klasifikasi.manage')
                                <td class="px-5 py-3">
                                    @if ($baris->tipe->value === 'individual')
                                        <button type="button" wire:click="setIndividual('{{ $baris->nokontrak }}', false)" class="min-h-11 text-danger-600 hover:underline">Jadikan kolektif</button>
                                    @else
                                        <button type="button" wire:click="setIndividual('{{ $baris->nokontrak }}', true)" class="min-h-11 text-primary-700 hover:underline">Jadikan individual</button>
                                    @endif
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-surface-500">Belum ada klasifikasi untuk periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-surface-200 px-5 py-3">{{ $klasifikasi->links() }}</div>
    </div>
</div>
