@extends('layouts.app')
@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">Ringkasan CKPN</h2>
        <p class="mt-1 text-sm text-surface-500">Pantau data pembiayaan dan periode penilaian.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="card">
            <p class="text-sm text-surface-500">Total Pembiayaan</p>
            <p class="mt-2 text-3xl font-bold text-surface-900">{{ number_format($totalPembiayaan) }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-surface-500">Total Periode</p>
            <p class="mt-2 text-3xl font-bold text-surface-900">{{ number_format($totalPeriode) }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-surface-500">Periode Terakhir</p>
            <p class="mt-2 text-3xl font-bold text-primary-700">{{ $periodeTerakhir?->periode ?? '-' }}</p>
            <p class="text-sm text-surface-500">{{ $periodeTerakhir?->status?->label() ?? 'Belum tersedia' }}</p>
        </div>
    </div>

    <h3 class="mb-4 mt-8 text-lg font-semibold text-surface-900">Run CKPN Terakhir</h3>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <div class="card">
            <p class="text-sm text-surface-500">Periode Run</p>
            <p class="mt-2 text-3xl font-bold text-primary-700">{{ $latestCompletedRun?->periode ?? '-' }}</p>
            <p class="text-sm text-surface-500">{{ $latestCompletedRun ? 'Selesai' : 'Belum tersedia' }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-surface-500">Total EAD</p>
            <p class="mt-2 text-2xl font-bold text-surface-900">{{ $latestCompletedRun ? number_format((float) $latestCompletedRun->total_ead, 2, ',', '.') : '-' }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-surface-500">Total CKPN</p>
            <p class="mt-2 text-2xl font-bold text-primary-700">{{ $latestCompletedRun ? number_format((float) $latestCompletedRun->total_ckpn, 2, ',', '.') : '-' }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-surface-500">Total PPKA</p>
            <p class="mt-2 text-2xl font-bold text-surface-900">{{ $latestCompletedRun ? number_format((float) $latestCompletedRun->total_ppka, 2, ',', '.') : '-' }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-surface-500">Cadangan PPKA Tambahan</p>
            <p class="mt-2 text-2xl font-bold text-accent-700">{{ $latestCompletedRun ? number_format((float) $latestCompletedRun->cadangan_ppka_tambahan, 2, ',', '.') : '-' }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-surface-500">Jumlah Hasil</p>
            <p class="mt-2 text-3xl font-bold text-surface-900">{{ $latestCompletedRun ? number_format($latestCompletedRun->hasil_count) : '-' }}</p>
        </div>
    </div>
@endsection
