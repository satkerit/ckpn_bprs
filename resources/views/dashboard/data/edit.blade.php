@extends('layouts.app')
@section('content')
    @php
        // Pilihan produk membawa kode akadnya agar dropdown dapat disaring
        // mengikuti kode akad yang dipilih (lihat resources/js/app.js).
        $produkOptions = array_map(function ($produk): array {
            $kdprd = is_array($produk) ? ($produk['kdprd'] ?? '') : (data_get($produk, 'kdprd') ?? '');
            $nama = is_array($produk) ? ($produk['nama'] ?? '') : (data_get($produk, 'nama') ?? '');
            $pokpby = is_array($produk) ? ($produk['pokpby'] ?? '') : (data_get($produk, 'pokpby') ?? '');

            return [
                'value' => $kdprd,
                'label' => $kdprd.' — '.$nama,
                'akad' => $pokpby,
            ];
        }, (array) ($produks ?? []));

        $fields = match ($jenis->value) {
            'pembiayaan' => [
                ['name' => 'nokontrak', 'label' => 'No Kontrak', 'required' => true],
                ['name' => 'nocif', 'label' => 'No CIF', 'required' => true],
                ['name' => 'nama', 'label' => 'Nama', 'required' => true, 'wide' => true],
                ['name' => 'kdprd', 'label' => 'Kode Produk', 'required' => true, 'type' => 'select', 'role' => 'produk', 'options' => $produkOptions],
                ['name' => 'kdloc', 'label' => 'Kode Kantor', 'required' => true],
                ['name' => 'pokpby', 'label' => 'Kode Akad', 'required' => true, 'type' => 'select', 'options' => $kodeAkads, 'role' => 'akad'],
                ['name' => 'gunadeb', 'label' => 'Guna Debitur', 'required' => true],
                ['name' => 'tglwo', 'label' => 'Tgl Write-off', 'type' => 'date'],
            ],
            'history' => [
                ['name' => 'nokontrak', 'label' => 'No Kontrak', 'required' => true],
                ['name' => 'periode', 'label' => 'Periode', 'required' => true],
                ['name' => 'kdprd', 'label' => 'Kode Produk', 'required' => true, 'type' => 'select', 'role' => 'produk', 'options' => $produkOptions],
                ['name' => 'kdloc', 'label' => 'Kode Kantor', 'required' => true],
                ['name' => 'pokpby', 'label' => 'Kode Akad', 'required' => true, 'type' => 'select', 'options' => $kodeAkads, 'role' => 'akad'],
                ['name' => 'tglexp', 'label' => 'Tgl Jatuh Tempo', 'type' => 'date'],
                ['name' => 'osmdlc', 'label' => 'OS Pokok', 'type' => 'number', 'required' => true],
                ['name' => 'osmgnc', 'label' => 'OS Margin', 'type' => 'number'],
                ['name' => 'tgkmdl', 'label' => 'Tunggakan Pokok', 'type' => 'number'],
                ['name' => 'tgkmgn', 'label' => 'Tunggakan Margin', 'type' => 'number'],
                ['name' => 'haritgk', 'label' => 'Hari Tunggakan', 'type' => 'number'],
                ['name' => 'col', 'label' => 'Kolektibilitas', 'type' => 'number'],
                ['name' => 'stsrec', 'label' => 'Status Recovery'],
                ['name' => 'stsacc', 'label' => 'Status Account'],
                ['name' => 'ppka', 'label' => 'PPKA', 'type' => 'number'],
            ],
            'jaminan' => [
                ['name' => 'nokontrak', 'label' => 'No Kontrak', 'required' => true],
                ['name' => 'noreg', 'label' => 'No Registrasi', 'required' => true],
                ['name' => 'urut', 'label' => 'Urut', 'type' => 'number', 'required' => true],
                ['name' => 'tgltaks', 'label' => 'Tgl Taksasi', 'type' => 'date'],
                ['name' => 'jnsjamin', 'label' => 'Jenis Jaminan', 'required' => true, 'type' => 'select', 'options' => $setupJaminans],
                ['name' => 'nominallikuid', 'label' => 'Nominal Likuidasi', 'type' => 'number', 'required' => true],
            ],
            'kantor' => [
                ['name' => 'kdloc', 'label' => 'Kode Kantor', 'required' => true],
                ['name' => 'nama', 'label' => 'Nama', 'required' => true, 'wide' => true],
                ['name' => 'alamat', 'label' => 'Alamat', 'wide' => true],
            ],
            'produk' => [
                ['name' => 'kdprd', 'label' => 'Kode Produk', 'required' => true],
                ['name' => 'nama', 'label' => 'Nama', 'required' => true, 'wide' => true],
                ['name' => 'pokpby', 'label' => 'Kode Akad', 'required' => true, 'type' => 'select', 'options' => $kodeAkads, 'role' => 'akad'],
            ],
            'jaminan_setup' => [
                ['name' => 'kdjam', 'label' => 'Kode Jaminan (kdjam)', 'required' => true],
                ['name' => 'ket', 'label' => 'Keterangan / Jenis Jaminan', 'required' => true, 'wide' => true],
                ['name' => 'bobot', 'label' => 'Bobot Pengurang (%)', 'type' => 'number', 'required' => true],
            ],
        };
        $itemId = match ($jenis->value) {
            'pembiayaan' => $item->nokontrak,
            'history' => $item->id,
            'jaminan' => $item->id,
            'kantor' => $item->kdloc,
            'produk' => $item->kdprd,
            'jaminan_setup' => $item->kdjam,
        };
    @endphp

    <div class="mb-6">
        <a href="{{ route('data.index', ['jenis' => $jenis->value, 'tab' => 'data']) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">&larr; Kembali</a>
        <h2 class="mt-2 text-2xl font-bold tracking-tight text-surface-900">{{ $title }}</h2>
    </div>

    @if ($errors->any())
        <x-alert type="danger" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    <form method="POST" action="{{ route('data.update', ['jenis' => $jenis->value, 'id' => $itemId]) }}" class="card max-w-5xl" data-confirm="Simpan perubahan data ini?">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($fields as $field)
                <div class="{{ ($field['wide'] ?? false) ? 'sm:col-span-2 lg:col-span-2' : '' }}">
                    @php
                        $value = old($field['name'], data_get($item, $field['name']));
                        // Kolom tanggal tersimpan sebagai Ymd, sedangkan input date memakai Y-m-d.
                        if (($field['type'] ?? '') === 'date' && is_string($value) && preg_match('/^(\d{8}|\d{4}-\d{2}-\d{2})$/', $value)) {
                            $value = \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
                        }
                    @endphp
                    <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
                    @if (($field['type'] ?? '') === 'select')
                        <select id="{{ $field['name'] }}" name="{{ $field['name'] }}"
                                @required($field['required'] ?? false)
                                @if (! empty($field['role'])) data-{{ $field['role'] }}-select @endif
                                class="form-input px-3 py-2.5">
                            <option value="">— Pilih —</option>
                            @foreach ($field['options'] as $optionKey => $option)
                                @php
                                    $optionValue = is_array($option) ? $option['value'] : $optionKey;
                                    $optionLabel = is_array($option) ? $option['label'] : $option;
                                @endphp
                                <option value="{{ $optionValue }}"
                                        data-akad="{{ is_array($option) ? ($option['akad'] ?? '') : '' }}"
                                        @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @else
                        <input id="{{ $field['name'] }}" name="{{ $field['name'] }}" type="{{ $field['type'] ?? 'text' }}" value="{{ $value }}" @required($field['required'] ?? false) @if (($field['type'] ?? '') === 'number') step="0.01" min="0" @endif class="form-input px-3 py-2.5">
                    @endif
                    @error($field['name'])<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror
                </div>
            @endforeach
            @if ($jenis->value === 'produk')
                <label class="flex min-h-11 items-center gap-2 self-end text-sm text-surface-700">
                    <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $item->aktif)) class="h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500"> Aktif
                </label>
            @endif
        </div>
        <div class="mt-6 flex flex-wrap gap-3 border-t border-surface-200 pt-4">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('data.show', ['jenis' => $jenis->value, 'id' => $itemId]) }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
@endsection
