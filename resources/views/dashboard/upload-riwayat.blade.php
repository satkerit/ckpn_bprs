@extends('layouts.app')
@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">Riwayat Upload</h2>
        <p class="mt-1 text-sm text-surface-500">Daftar semua batch data yang telah diunggah.</p>
    </div>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-surface-200 text-sm">
                <thead class="bg-surface-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Jenis</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Periode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">File</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">Baris</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">Sukses</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-surface-500">Gagal</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-surface-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Oleh</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Tgl</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-200 bg-white">
                    @forelse ($batches as $batch)
                        <tr class="hover:bg-surface-50">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-surface-900">{{ $batch->jenis->label() }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-surface-600">{{ $batch->periode }}</td>
                            <td class="px-4 py-3 font-mono text-surface-600">{{ $batch->file_name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-surface-600">{{ $batch->total_baris }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-success-600">{{ $batch->baris_sukses }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-danger-600">{{ $batch->baris_gagal }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                @php
                                    $statusClass = match ($batch->status) {
                                        'completed' => 'bg-success-50 text-success-700',
                                        'completed_with_errors' => 'bg-accent-50 text-accent-700',
                                        'failed' => 'bg-danger-50 text-danger-700',
                                        'processing' => 'bg-surface-100 text-surface-600',
                                        default => 'bg-surface-100 text-surface-600',
                                    };
                                    $statusLabel = match ($batch->status) {
                                        'completed' => 'Selesai',
                                        'completed_with_errors' => 'Selesai dengan error',
                                        'failed' => 'Gagal',
                                        'processing' => 'Diproses',
                                        default => ucfirst((string) $batch->status),
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-4 py-3 text-surface-600">{{ $batch->pengunggah?->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-surface-500">{{ $batch->created_at->translatedFormat('d M Y H:i') }}</td>
                        </tr>
                        @if (! empty($batch->error_log))
                            <tr class="bg-surface-50">
                                <td colspan="9" class="px-4 py-3">
                                    <details>
                                        <summary class="cursor-pointer text-xs font-semibold text-danger-700">
                                            Lihat alasan {{ count($batch->error_log) }} baris gagal
                                        </summary>
                                        <ul class="mt-2 space-y-1.5 text-xs text-surface-600">
                                            @foreach (array_slice($batch->error_log, 0, 20) as $error)
                                                <li class="rounded border border-rose-100 bg-white p-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-bold text-surface-800">Baris {{ $error['baris'] ?? '?' }}</span>
                                                        @if (! empty($error['kolom']))
                                                            <span class="rounded bg-danger-50 px-1.5 py-0.5 text-[10px] font-semibold text-danger-700 uppercase">
                                                                kolom: {{ $error['kolom'] }}
                                                            </span>
                                                        @endif
                                                        @if (isset($error['nilai']) && $error['nilai'] !== '')
                                                            <span class="text-surface-500 font-mono text-[11px]">
                                                                (nilai ditolak: "{{ $error['nilai'] }}")
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="mt-1 font-medium text-danger-700">
                                                        {{ $error['message'] ?? 'Baris gagal diproses.' }}
                                                    </div>
                                                    @if (! empty($error['row']))
                                                        <div class="mt-1 font-mono text-[11px] text-surface-400 break-all">
                                                            Data mentah: {{ \Illuminate\Support\Str::limit((string) json_encode($error['row']), 200) }}
                                                        </div>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if (count($batch->error_log) > 20)
                                            <p class="mt-1 text-xs text-surface-500">Menampilkan 20 dari {{ count($batch->error_log) }} baris gagal.</p>
                                        @endif
                                        <div class="mt-3 flex items-center gap-3">
                                            <a href="{{ route('upload.error', $batch) }}" class="btn btn-secondary px-3 py-1.5 text-xs">
                                                Unduh laporan error (.xlsx)
                                            </a>
                                            <a href="{{ route('upload.error', ['batch' => $batch, 'format' => 'csv']) }}" class="text-xs font-semibold text-primary-700 hover:text-primary-800">
                                                CSV
                                            </a>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="9" class="px-4 py-8 text-center text-surface-500">Belum ada upload.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($batches->hasPages())
            <div class="border-t border-surface-200 px-4 py-3">{{ $batches->links() }}</div>
        @endif
    </div>
@endsection
