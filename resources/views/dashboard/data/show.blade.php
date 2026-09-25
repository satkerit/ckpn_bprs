@extends('layouts.app')
@section('content')
    <div class="mb-6">
        <a href="{{ route('data.index', ['jenis' => $jenis->value, 'tab' => 'data']) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
            &larr; Kembali ke Data {{ $jenis->label() }}
        </a>
        <h2 class="mt-2 text-2xl font-bold tracking-tight text-surface-900">{{ $title }}</h2>
    </div>

    <div class="card max-w-4xl">
        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3">
            @if ($jenis->value === 'pembiayaan')
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">No Kontrak</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->nokontrak }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">No CIF</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->nocif }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3 sm:col-span-2 md:col-span-1">
                    <span class="block text-xs font-medium text-surface-500">Nama Nasabah</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->nama }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Kode Produk</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->kdprd }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Kode Kantor</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->kdloc }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Akad</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->pokpby }}{{ $item->akad ? ' — '.$item->akad->nama : '' }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Guna Debitur</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->gunadeb ?? '-' }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Tgl Write-off</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->tglwo ? \Carbon\Carbon::parse($item->tglwo)->format('d/m/Y') : '-' }}</span>
                </div>
            @elseif ($jenis->value === 'history')
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">No Kontrak</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->nokontrak }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Periode</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->periode }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Kode Produk</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->kdprd }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Kode Kantor</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->kdloc }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Akad</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->pokpby }}{{ $item->akad ? ' — '.$item->akad->nama : '' }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Tgl Jatuh Tempo</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->tglexp ? \Carbon\Carbon::parse($item->tglexp)->format('d/m/Y') : '-' }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">OS Pokok</span>
                    <span class="text-sm font-semibold text-surface-900">Rp {{ number_format((float) $item->osmdlc, 2, ',', '.') }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">OS Margin</span>
                    <span class="text-sm font-semibold text-surface-900">Rp {{ number_format((float) ($item->osmgnc ?? 0), 2, ',', '.') }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Tunggakan Pokok</span>
                    <span class="text-sm font-semibold text-surface-900">Rp {{ number_format((float) ($item->tgkmdl ?? 0), 2, ',', '.') }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Tunggakan Margin</span>
                    <span class="text-sm font-semibold text-surface-900">Rp {{ number_format((float) ($item->tgkmgn ?? 0), 2, ',', '.') }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Hari Tunggakan</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->haritgk ?? 0 }} hari</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Kolektibilitas</span>
                    <span class="text-sm font-semibold text-surface-900">Kol {{ $item->col ?? '-' }}</span>
                </div>
            @elseif ($jenis->value === 'jaminan')
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">No Kontrak</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->nokontrak }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">No Registrasi</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->noreg }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Urut</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->urut }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Jenis Jaminan</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->jnsjamin }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Tgl Taksasi</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->tgltaks ? \Carbon\Carbon::parse($item->tgltaks)->format('d/m/Y') : '-' }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Nominal Likuidasi</span>
                    <span class="text-sm font-semibold text-surface-900">Rp {{ number_format((float) $item->nominallikuid, 2, ',', '.') }}</span>
                </div>
            @elseif ($jenis->value === 'kantor')
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Kode Kantor</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->kdloc }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Nama Kantor</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->nama }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3 sm:col-span-2 md:col-span-3">
                    <span class="block text-xs font-medium text-surface-500">Alamat</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->alamat ?? '-' }}</span>
                </div>
            @elseif ($jenis->value === 'produk')
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Kode Produk</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->kdprd }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Nama Produk</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->nama }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Kode Akad</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->pokpby ?? '-' }}{{ $item->pokpby && $item->akad ? ' — '.$item->akad->nama : '' }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Status</span>
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $item->aktif ? 'bg-success-50 text-success-700' : 'bg-surface-100 text-surface-600' }}">
                        {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            @elseif ($jenis->value === 'jaminan_setup')
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Kode Jaminan (kdjam)</span>
                    <span class="text-sm font-semibold text-surface-900 font-mono">{{ $item->kdjam }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3 sm:col-span-2">
                    <span class="block text-xs font-medium text-surface-500">Keterangan / Jenis Jaminan</span>
                    <span class="text-sm font-semibold text-surface-900">{{ $item->ket }}</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Bobot Pengurang</span>
                    <span class="text-sm font-semibold text-surface-900 font-mono">{{ number_format((float) $item->bobot, 2, ',', '.') }}%</span>
                </div>
                <div class="rounded-lg bg-surface-50 p-3">
                    <span class="block text-xs font-medium text-surface-500">Jumlah Data Agunan Terkait</span>
                    <span class="text-sm font-semibold text-surface-900 font-mono">{{ $item->agunan()->count() }} data</span>
                </div>
            @endif
        </div>

        <div class="mt-6 flex gap-3 border-t border-surface-200 pt-4">
            @php
                $itemId = match ($jenis->value) {
                    'pembiayaan' => $item->nokontrak,
                    'history' => $item->id,
                    'jaminan' => $item->id,
                    'kantor' => $item->kdloc,
                    'produk' => $item->kdprd,
                    'jaminan_setup' => $item->kdjam,
                };
            @endphp
            <a href="{{ route('data.edit', ['jenis' => $jenis->value, 'id' => $itemId]) }}" class="btn btn-primary">
                Edit Data
            </a>
            <a href="{{ route('data.index', ['jenis' => $jenis->value, 'tab' => 'manual']) }}" class="btn btn-secondary">
                Tutup
            </a>
        </div>
    </div>
@endsection
