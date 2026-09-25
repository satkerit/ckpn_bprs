<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">Hasil CKPN</h2>
        <p class="mt-1 text-sm text-surface-500">Detail per rekening dan ringkasan per kantor.</p>
    </div>

    <div class="card mb-6">
        <p class="form-label">Pilih run</p>
        <div class="flex flex-wrap gap-2">
            @forelse ($runs as $item)
                <button wire:click="pilihRun({{ $item->id }})"
                        class="rounded-lg border px-3 py-2 text-sm font-medium transition {{ $run?->id === $item->id ? 'border-primary-600 bg-primary-50 text-primary-700' : 'border-surface-300 bg-white text-surface-600 hover:bg-surface-50' }}">
                    Run #{{ $item->id }} / {{ $item->periode }}
                </button>
            @empty
                <p class="text-sm text-surface-500">Belum ada run CKPN.</p>
            @endforelse
        </div>
    </div>

    @if ($run)
        <div class="mb-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="card">
                <p class="text-sm text-surface-500">EAD</p>
                <p class="mt-1 text-2xl font-bold text-surface-900">{{ number_format((float) $run->total_ead, 2, ',', '.') }}</p>
            </div>
            <div class="card">
                <p class="text-sm text-surface-500">CKPN PSAK 414</p>
                <p class="mt-1 text-2xl font-bold text-primary-700">{{ number_format((float) $run->total_ckpn, 2, ',', '.') }}</p>
            </div>
            <div class="card">
                <p class="text-sm text-surface-500">PPKA</p>
                <p class="mt-1 text-2xl font-bold text-surface-900">{{ number_format((float) $run->total_ppka, 2, ',', '.') }}</p>
            </div>
            <div class="card">
                <p class="text-sm text-surface-500">Cadangan Tambahan</p>
                <p class="mt-1 text-2xl font-bold text-accent-700">{{ number_format((float) $run->cadangan_ppka_tambahan, 2, ',', '.') }}</p>
            </div>
        </div>

        @can('ckpn.calculate')
            <button wire:click="ringkasKantor" class="btn btn-secondary mb-4">Buat Ringkasan Kantor</button>
        @endcan

        <div class="card overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Kontrak</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Kantor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Bucket</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">EAD</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">PD</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">LGD</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">CKPN</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">PPKA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-200 bg-white">
                        @forelse ($hasil as $row)
                            <tr class="hover:bg-surface-50">
                                <td class="px-4 py-3 font-medium text-surface-900">{{ $row->nokontrak }}</td>
                                <td class="px-4 py-3 text-surface-600">{{ $row->kdloc }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded bg-surface-100 px-2 py-0.5 text-xs font-medium text-surface-600">{{ $row->bucket }}</span>
                                </td>
                                <td class="px-4 py-3 text-right text-surface-600">{{ number_format((float) $row->ead, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-surface-600">{{ number_format((float) $row->pd_netflow * 100, 2) }}%</td>
                                <td class="px-4 py-3 text-right text-surface-600">{{ number_format((float) $row->lgd_persen * 100, 2) }}%</td>
                                <td class="px-4 py-3 text-right font-semibold text-primary-700">{{ number_format((float) $row->ckpn_psak414, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-surface-600">{{ number_format((float) $row->ppka_wajib, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-surface-500">Belum ada hasil.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="card py-10 text-center text-surface-500">Belum ada run CKPN.</div>
    @endif
</div>
