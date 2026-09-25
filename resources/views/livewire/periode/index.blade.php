<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">Periode CKPN</h2>
        <p class="mt-1 text-sm text-surface-500">Buat, kunci, atau buka periode perhitungan.</p>
    </div>

    @if (session('status'))
        <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    @can('periode.manage')
        <form wire:submit="simpan" class="card mb-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="form-label" for="periode">Periode</label>
                    <input id="periode" wire:model="periode" placeholder="YYYYMM" maxlength="6" class="form-input px-3 py-2.5">
                    @error('periode') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <input id="keterangan" wire:model="keterangan" class="form-input px-3 py-2.5">
                    @error('keterangan') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn btn-primary w-full">Buat Periode</button>
                </div>
            </div>
        </form>
    @endcan

    <div class="card overflow-hidden p-0">
        <table class="min-w-full text-sm">
            <thead class="bg-surface-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Periode</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Keterangan</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-200 bg-white">
                @forelse ($periodes as $item)
                    <tr class="hover:bg-surface-50">
                        <td class="px-5 py-3 font-medium text-surface-900">{{ $item->label() }}</td>
                        <td class="px-5 py-3">
                            @php
                                $statusClass = $item->isLocked() ? 'bg-accent-50 text-accent-700' : 'bg-success-50 text-success-700';
                            @endphp
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $item->status->label() }}</span>
                        </td>
                        <td class="px-5 py-3 text-surface-600">{{ $item->keterangan ?? '-' }}</td>
                        <td class="px-5 py-3">
                            @can('periode.manage')
                                @if (!$item->isLocked())
                                    <button wire:click="kunci({{ $item->id }})" class="min-h-11 rounded text-accent-700 hover:underline">Kunci</button>
                                @endif
                            @endcan
                            @can('periode.unlock')
                                @if ($item->status !== \App\Enums\StatusPeriode::Final && $item->isLocked())
                                    <button wire:click="buka({{ $item->id }})" class="ml-3 min-h-11 rounded text-primary-700 hover:underline">Buka</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-surface-500">Belum ada periode.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
