<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">PD Migration</h2>
        <p class="mt-1 text-sm text-surface-500">Hitung probabilitas default kumulatif dari matriks migrasi bucket.</p>
    </div>
    @if (session('status'))
        <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @can('pd.calculate')
        <form wire:submit="hitung" class="card mb-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="form-label" for="periode">Periode</label>
                    <input id="periode" wire:model="periode" placeholder="YYYYMM" maxlength="6" class="form-input px-3 py-2.5">
                    @error('periode') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="lookback">Lookback</label>
                    <select id="lookback" wire:model="lookbackBulan" class="form-input px-3 py-2.5">
                        @foreach ([12, 24, 36, 60] as $bulan)
                            <option value="{{ $bulan }}">{{ $bulan }} bulan</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-full">
                        <span wire:loading.remove wire:target="hitung">Hitung PD</span>
                        <span wire:loading wire:target="hitung">Memproses... <i class="spinner"></i></span>
                    </button>
                </div>
            </div>
        </form>
    @endcan

    <div class="card overflow-hidden p-0">
        <table class="min-w-full text-sm">
            <thead class="bg-surface-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Run</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Periode</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Lookback</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-200 bg-white">
                @forelse ($runs as $run)
                    <tr class="hover:bg-surface-50">
                        <td class="px-5 py-3 font-medium text-surface-900">#{{ $run->id }}</td>
                        <td class="px-5 py-3 text-surface-600">{{ $run->periode }}</td>
                        <td class="px-5 py-3 text-surface-600">{{ $run->lookback_bulan }} bulan</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex rounded-full bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700">{{ $run->status->label() }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-surface-500">Belum ada perhitungan PD Migration.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
