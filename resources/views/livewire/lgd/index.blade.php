<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">LGD</h2>
        <p class="mt-1 text-sm text-surface-500">Kelola parameter agunan dan hitung recovery.</p>
    </div>
    @if (session('status'))
        <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @can('lgd.input')
        <form wire:submit="simpanParameter" class="card mb-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="form-label" for="periode">Periode</label>
                    <input id="periode" wire:model="periode" placeholder="YYYYMM" class="form-input px-3 py-2.5">
                </div>
                <div>
                    <label class="form-label" for="segment">Segment</label>
                    <input id="segment" wire:model="segmentKey" placeholder="Segment key (opsional)" class="form-input px-3 py-2.5">
                </div>
                <div>
                    <label class="form-label" for="jnsjamin">Jenis jaminan</label>
                    <input id="jnsjamin" wire:model="jnsjamin" placeholder="Jenis jaminan" class="form-input px-3 py-2.5">
                </div>
                <div>
                    <label class="form-label" for="haircut">Haircut %</label>
                    <input id="haircut" wire:model="haircutPersen" type="number" min="0" max="100" step="0.01" placeholder="Haircut %" class="form-input px-3 py-2.5">
                </div>
                <div>
                    <label class="form-label" for="biaya">Biaya lelang %</label>
                    <input id="biaya" wire:model="biayaLelangPersen" type="number" min="0" max="100" step="0.01" placeholder="Biaya lelang %" class="form-input px-3 py-2.5">
                </div>
                <div>
                    <label class="form-label" for="metode">Metode</label>
                    <select id="metode" wire:model="metode" class="form-input px-3 py-2.5">
                        <option value="shortfall">Collateral Shortfall</option>
                        <option value="expected_recovery">Expected Recovery</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-5">Simpan Parameter</button>
        </form>
    @endcan

    @can('lgd.calculate')
        <button wire:click="hitung" wire:loading.attr="disabled" class="btn btn-secondary mb-6">
            <span wire:loading.remove wire:target="hitung">Hitung LGD</span>
            <span wire:loading wire:target="hitung">Memproses... <i class="spinner"></i></span>
        </button>
    @endcan

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Periode</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Metode</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">EAD Default</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">Recovery</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">LGD</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-200 bg-white">
                    @forelse ($hasil as $row)
                        <tr class="hover:bg-surface-50">
                            <td class="px-5 py-3 text-surface-600">{{ $row->run?->periode ?? '-' }}</td>
                            <td class="px-5 py-3 text-surface-600">{{ $row->metode }}</td>
                            <td class="px-5 py-3 text-right font-medium text-surface-900">{{ number_format((float) $row->total_ead_default, 2) }}</td>
                            <td class="px-5 py-3 text-right text-surface-600">{{ number_format((float) $row->total_penerimaan, 2) }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-danger-600">{{ number_format((float) $row->lgd_persen * 100, 2) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-surface-500">Belum ada hasil LGD.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
