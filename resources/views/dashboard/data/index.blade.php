@extends('layouts.app')
@section('content')
    @php
        $tab = $tab ?? 'upload';
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-surface-900">{{ $jenis->label() }}</h2>
            <p class="mt-1 text-sm text-surface-500">Upload file, download template, atau kelola data manual — semua dalam satu halaman.</p>
        </div>
        <a href="{{ route('data.template', ['jenis' => $jenis->value]) }}" class="btn btn-secondary">Download Template</a>
    </div>

    @if (session('status'))
        <x-alert type="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert type="danger" class="mb-4">{{ $errors->first() }}</x-alert>
    @endif

    {{-- Tab nav --}}
    <div class="mb-6 flex gap-1 border-b border-surface-200">
        <a href="{{ route('data.index', ['jenis' => $jenis->value, 'tab' => 'upload']) }}"
           class="px-4 py-2.5 text-sm font-semibold transition {{ $tab === 'upload' ? 'border-b-2 border-primary-600 text-primary-700' : 'text-surface-500 hover:text-surface-700' }}">
            Upload File
        </a>
        <a href="{{ route('data.index', ['jenis' => $jenis->value, 'tab' => 'manual']) }}"
           class="px-4 py-2.5 text-sm font-semibold transition {{ $tab === 'manual' ? 'border-b-2 border-primary-600 text-primary-700' : 'text-surface-500 hover:text-surface-700' }}">
            Input Manual
        </a>
        <a href="{{ route('data.index', ['jenis' => $jenis->value, 'tab' => 'data']) }}"
           class="px-4 py-2.5 text-sm font-semibold transition {{ $tab === 'data' ? 'border-b-2 border-primary-600 text-primary-700' : 'text-surface-500 hover:text-surface-700' }}">
            Data Terupload
        </a>
    </div>

    @if ($tab === 'upload')
        {{-- Tab Upload --}}
        <form method="POST" action="{{ route('upload.store') }}" enctype="multipart/form-data" data-upload-form class="card mb-8">
            @csrf
            <input type="hidden" name="jenis" value="{{ $jenis->value }}">
            <div>
                <label for="file" class="form-label">File {{ $jenis->label() }}</label>
                <input type="file" id="file" name="file" accept=".xlsx,.xls,.csv" required
                       class="block w-full cursor-pointer rounded-lg border border-surface-300 text-sm text-surface-600 file:mr-4 file:cursor-pointer file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100">
                <p class="mt-2 text-xs text-surface-500">Format: .xlsx, .xls, .csv. Maksimal 50 MB. Periode dibaca dari kolom data history.</p>
            </div>
            <div class="mt-5 flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Upload Data</button>
                @if ($jenis->value === 'pembiayaan')
                    <button type="button" class="btn btn-danger" data-truncate-btn
                            data-action="{{ route('data.truncate', ['jenis' => $jenis->value]) }}">
                        Kosongkan Data Pembiayaan
                    </button>
                @endif
            </div>
        </form>

    @elseif ($tab === 'manual')
        {{-- Tab Input Manual --}}
        <form method="POST" action="{{ route('data.store', ['jenis' => $jenis->value]) }}" class="card mb-8">
            @csrf @method('POST')

            @if ($jenis->value === 'pembiayaan')
                {{-- Grid 12 kolom untuk proporsi field --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-12">
                    <div class="md:col-span-3">
                        <label for="nokontrak" class="form-label">No Kontrak</label>
                        <input id="nokontrak" name="nokontrak" value="{{ old('nokontrak') }}" required placeholder="Contoh: 0101230001" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-3">
                        <label for="nocif" class="form-label">No CIF</label>
                        <input id="nocif" name="nocif" value="{{ old('nocif') }}" required placeholder="Contoh: CIF000123" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-6">
                        <label for="nama" class="form-label">Nama Nasabah</label>
                        <input id="nama" name="nama" value="{{ old('nama') }}" required placeholder="Nama lengkap debitur" class="form-input px-3 py-2.5">
                    </div>

                    <div class="md:col-span-3">
                        <label for="kdprd" class="form-label">Kode Produk</label>
                        <select id="kdprd" name="kdprd" required data-produk-select class="form-input px-3 py-2.5">
                            <option value="">— Pilih Produk —</option>
                            @foreach ($produks as $produk)
                                @php
                                    $kdprd = is_array($produk) ? $produk['kdprd'] : $produk->kdprd;
                                    $nama = is_array($produk) ? $produk['nama'] : $produk->nama;
                                    $pokpby = is_array($produk) ? $produk['pokpby'] : $produk->pokpby;
                                @endphp
                                <option value="{{ $kdprd }}" data-akad="{{ $pokpby }}" @selected((string) old('kdprd') === (string) $kdprd)>
                                    {{ $kdprd }} — {{ $nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="kdloc" class="form-label">Kode Kantor</label>
                        <input id="kdloc" name="kdloc" value="{{ old('kdloc') }}" required placeholder="001" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-3">
                        <label for="pokpby" class="form-label">Kode Akad</label>
                        <select id="pokpby" name="pokpby" required data-akad-select class="form-input px-3 py-2.5">
                            <option value="">— Pilih Akad —</option>
                            @foreach ($kodeAkads as $kode => $label)
                                <option value="{{ $kode }}" @selected((string) old('pokpby') === (string) $kode)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="gunadeb" class="form-label">Guna Debitur</label>
                        <input id="gunadeb" name="gunadeb" value="{{ old('gunadeb') }}" required placeholder="1" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-3">
                        <label for="tglwo" class="form-label">Tgl Write-off</label>
                        <input id="tglwo" name="tglwo" type="date" value="{{ old('tglwo') }}" class="form-input px-3 py-2.5">
                    </div>
                </div>

            @elseif ($jenis->value === 'history')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-12">
                    <div class="md:col-span-3">
                        <label for="nokontrak" class="form-label">No Kontrak</label>
                        <input id="nokontrak" name="nokontrak" value="{{ old('nokontrak') }}" required class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-2">
                        <label for="periode" class="form-label">Periode</label>
                        <input id="periode" name="periode" value="{{ old('periode') }}" placeholder="YYYYMM" required class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-2">
                        <label for="kdprd" class="form-label">Kode Produk</label>
                        <select id="kdprd" name="kdprd" required data-produk-select class="form-input px-3 py-2.5">
                            <option value="">— Pilih Produk —</option>
                            @foreach ($produks as $produk)
                                @php
                                    $kdprd = is_array($produk) ? $produk['kdprd'] : $produk->kdprd;
                                    $nama = is_array($produk) ? $produk['nama'] : $produk->nama;
                                    $pokpby = is_array($produk) ? $produk['pokpby'] : $produk->pokpby;
                                @endphp
                                <option value="{{ $kdprd }}" data-akad="{{ $pokpby }}" @selected((string) old('kdprd') === (string) $kdprd)>
                                    {{ $kdprd }} — {{ $nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="kdloc" class="form-label">Kode Kantor</label>
                        <input id="kdloc" name="kdloc" value="{{ old('kdloc') }}" required class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-3">
                        <label for="pokpby" class="form-label">Kode Akad</label>
                        <select id="pokpby" name="pokpby" required data-akad-select class="form-input px-3 py-2.5">
                            <option value="">— Pilih Akad —</option>
                            @foreach ($kodeAkads as $kode => $label)
                                <option value="{{ $kode }}" @selected((string) old('pokpby') === (string) $kode)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label for="tglexp" class="form-label">Tgl Jatuh Tempo</label>
                        <input id="tglexp" name="tglexp" type="date" value="{{ old('tglexp') }}" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-3">
                        <label for="osmdlc" class="form-label">OS Pokok</label>
                        <input id="osmdlc" name="osmdlc" type="number" step="0.01" min="0" value="{{ old('osmdlc') }}" required class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-3">
                        <label for="osmgnc" class="form-label">OS Margin</label>
                        <input id="osmgnc" name="osmgnc" type="number" step="0.01" min="0" value="{{ old('osmgnc') }}" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-3">
                        <label for="tgkmdl" class="form-label">Tunggakan Pokok</label>
                        <input id="tgkmdl" name="tgkmdl" type="number" step="0.01" min="0" value="{{ old('tgkmdl') }}" class="form-input px-3 py-2.5">
                    </div>

                    <div class="md:col-span-3">
                        <label for="tgkmgn" class="form-label">Tunggakan Margin</label>
                        <input id="tgkmgn" name="tgkmgn" type="number" step="0.01" min="0" value="{{ old('tgkmgn') }}" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-2">
                        <label for="haritgk" class="form-label">Hari Tunggakan</label>
                        <input id="haritgk" name="haritgk" type="number" min="0" value="{{ old('haritgk') }}" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-2">
                        <label for="col" class="form-label">Kolektibilitas</label>
                        <input id="col" name="col" type="number" min="1" max="5" value="{{ old('col') }}" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-2">
                        <label for="stsrec" class="form-label">Status Rec</label>
                        <input id="stsrec" name="stsrec" value="{{ old('stsrec') }}" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-3">
                        <label for="ppka" class="form-label">PPKA</label>
                        <input id="ppka" name="ppka" type="number" step="0.01" min="0" value="{{ old('ppka') }}" class="form-input px-3 py-2.5">
                    </div>
                </div>
                <p class="mt-2 text-xs text-surface-500">Periode tidak boleh terkunci/final.</p>

            @elseif ($jenis->value === 'jaminan')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-12">
                    <div class="md:col-span-3">
                        <label for="nokontrak" class="form-label">No Kontrak</label>
                        <input id="nokontrak" name="nokontrak" value="{{ old('nokontrak') }}" required class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-3">
                        <label for="noreg" class="form-label">No Registrasi</label>
                        <input id="noreg" name="noreg" value="{{ old('noreg') }}" required class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-2">
                        <label for="urut" class="form-label">Urut</label>
                        <input id="urut" name="urut" type="number" min="1" value="{{ old('urut', 1) }}" required class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-4">
                        <label for="jnsjamin" class="form-label">Jenis Jaminan</label>
                        <select id="jnsjamin" name="jnsjamin" required class="form-input px-3 py-2.5">
                            <option value="" disabled selected>Pilih Jenis Jaminan</option>
                            @foreach($setupJaminans as $kdjam => $ket)
                                <option value="{{ $kdjam }}" {{ old('jnsjamin') == $kdjam ? 'selected' : '' }}>{{ $ket }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-4">
                        <label for="tgltaks" class="form-label">Tgl Taksasi</label>
                        <input id="tgltaks" name="tgltaks" type="date" value="{{ old('tgltaks') }}" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-4">
                        <label for="nominallikuid" class="form-label">Nominal Likuidasi</label>
                        <input id="nominallikuid" name="nominallikuid" type="number" step="0.01" min="0" value="{{ old('nominallikuid') }}" required class="form-input px-3 py-2.5">
                    </div>
                </div>

            @elseif ($jenis->value === 'kantor')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-12">
                    <div class="md:col-span-2">
                        <label for="kdloc" class="form-label">Kode Kantor</label>
                        <input id="kdloc" name="kdloc" value="{{ old('kdloc') }}" required placeholder="001" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-4">
                        <label for="nama" class="form-label">Nama Kantor</label>
                        <input id="nama" name="nama" value="{{ old('nama') }}" required placeholder="KC Pangkalpinang" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-6">
                        <label for="alamat" class="form-label">Alamat</label>
                        <input id="alamat" name="alamat" value="{{ old('alamat') }}" placeholder="Jl. Merdeka No. 12" class="form-input px-3 py-2.5">
                    </div>
                </div>

            @elseif ($jenis->value === 'produk')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-12">
                    <div class="md:col-span-2">
                        <label for="kdprd" class="form-label">Kode Produk</label>
                        <input id="kdprd" name="kdprd" value="{{ old('kdprd') }}" required placeholder="PRD01" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-5">
                        <label for="nama" class="form-label">Nama Produk</label>
                        <input id="nama" name="nama" value="{{ old('nama') }}" required placeholder="Pembiayaan Modal Kerja" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-3">
                        <label for="pokpby" class="form-label">Kode Akad</label>
                        <select id="pokpby" name="pokpby" required data-akad-select class="form-input px-3 py-2.5">
                            <option value="">— Pilih Akad —</option>
                            @foreach ($kodeAkads as $kode => $label)
                                <option value="{{ $kode }}" @selected((string) old('pokpby') === (string) $kode)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end md:col-span-2">
                        <label class="flex min-h-11 items-center gap-2 text-sm text-surface-700">
                            <input type="checkbox" name="aktif" value="1" @checked(old('aktif', true)) class="h-4 w-4 rounded border-surface-300 text-primary-600 focus:ring-primary-500"> Aktif
                        </label>
                    </div>
                </div>

            @elseif ($jenis->value === 'jaminan_setup')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-12">
                    <div class="md:col-span-3">
                        <label for="kdjam" class="form-label">Kode Jaminan (kdjam)</label>
                        <input id="kdjam" name="kdjam" value="{{ old('kdjam') }}" required placeholder="Contoh: 101" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-6">
                        <label for="ket" class="form-label">Keterangan / Jenis Jaminan</label>
                        <input id="ket" name="ket" value="{{ old('ket') }}" required placeholder="Contoh: Tanah dan Bangunan SHM" class="form-input px-3 py-2.5">
                    </div>
                    <div class="md:col-span-3">
                        <label for="bobot" class="form-label">Bobot Pengurang (%)</label>
                        <input type="number" step="0.01" min="0" max="100" id="bobot" name="bobot" value="{{ old('bobot', 100.00) }}" required placeholder="100.00" class="form-input px-3 py-2.5">
                        <p class="mt-1 text-[11px] text-surface-500">Persentase likuidasi jaminan (0 - 100)</p>
                    </div>
                </div>
            @endif

            <div class="mt-5 flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('data.index', ['jenis' => $jenis->value, 'tab' => 'manual']) }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>

    @else
        {{-- Tab Data Terupload --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-bold text-surface-900">Daftar Data {{ $jenis->label() }}</h3>
                <p class="text-xs text-surface-500">Seluruh data yang telah diupload / tersimpan pada database</p>
            </div>
            <form method="GET" action="{{ route('data.index', ['jenis' => $jenis->value]) }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="data">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Cari data..."
                           class="w-48 sm:w-64 rounded-lg border border-surface-300 py-1.5 pl-8 pr-3 text-xs focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    <svg class="absolute left-2.5 top-2 h-4 w-4 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button type="submit" class="btn btn-secondary py-1.5 text-xs">Cari</button>
                @if (!empty($search))
                    <a href="{{ route('data.index', ['jenis' => $jenis->value, 'tab' => 'data']) }}" class="text-xs text-rose-600 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <div class="card overflow-hidden p-0 mb-8 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-surface-200 text-xs sm:text-sm">
                    <thead class="bg-surface-50 text-surface-600 font-semibold uppercase tracking-wider text-[11px]">
                        <tr>
                            @if ($jenis->value === 'pembiayaan')
                                <th class="px-4 py-3 text-left whitespace-nowrap">No Kontrak</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Nama Nasabah</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Kantor</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Akad / Pokok</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Guna Debitur</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Tgl WO</th>
                            @elseif ($jenis->value === 'history')
                                <th class="px-4 py-3 text-left whitespace-nowrap">Periode</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">No Kontrak</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Kantor</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">OS Pokok</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Hari Tunggakan</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">Kolektibilitas</th>
                            @elseif ($jenis->value === 'jaminan')
                                <th class="px-4 py-3 text-left whitespace-nowrap">No Kontrak</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">No Reg</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Jenis Jaminan</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Nominal Pasar</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Nominal Likuidasi</th>
                            @elseif ($jenis->value === 'kantor')
                                <th class="px-4 py-3 text-left whitespace-nowrap">Kode</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Nama Kantor</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Alamat</th>
                            @elseif ($jenis->value === 'produk')
                                <th class="px-4 py-3 text-left whitespace-nowrap">Kode</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Nama Produk</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Akad / Pokok</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">Status</th>
                            @elseif ($jenis->value === 'jaminan_setup')
                                <th class="px-4 py-3 text-left whitespace-nowrap">Kode Jaminan (kdjam)</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Keterangan / Jenis</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Bobot Pengurang</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">Jml Terkait</th>
                            @endif
                            <th class="px-4 py-3 text-center whitespace-nowrap w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-200 bg-white">
                        @forelse ($items as $item)
                            @php
                                $itemId = match ($jenis->value) {
                                    'pembiayaan' => $item->nokontrak,
                                    'history' => $item->id,
                                    'jaminan' => $item->id,
                                    'kantor' => $item->kdloc,
                                    'produk' => $item->kdprd,
                                    'jaminan_setup' => $item->kdjam,
                                };
                                $itemName = match ($jenis->value) {
                                    'pembiayaan' => "Pembiayaan {$item->nokontrak} ({$item->nama})",
                                    'history' => "History {$item->nokontrak} Periode {$item->periode}",
                                    'jaminan' => "Jaminan {$item->noreg} Kontrak {$item->nokontrak}",
                                    'kantor' => "Kantor {$item->kdloc} ({$item->nama})",
                                    'produk' => "Produk {$item->kdprd} ({$item->nama})",
                                    'jaminan_setup' => "Setup Jaminan {$item->kdjam} ({$item->ket})",
                                };
                            @endphp
                            <tr class="hover:bg-surface-50/80 transition">
                                @if ($jenis->value === 'pembiayaan')
                                    <td class="px-4 py-3 font-semibold text-surface-900 whitespace-nowrap">{{ $item->nokontrak }}</td>
                                    <td class="px-4 py-3 text-surface-700 whitespace-nowrap">{{ $item->nama ?? '-' }}</td>
                                    <td class="px-4 py-3 text-surface-600 whitespace-nowrap">{{ $item->kdloc }}</td>
                                    <td class="px-4 py-3 text-surface-600 whitespace-nowrap">{{ $item->pokpby }}{{ $item->akad ? ' — '.$item->akad->nama : '' }}</td>
                                    <td class="px-4 py-3 text-surface-600 whitespace-nowrap">{{ $item->gunadeb ?? '-' }}</td>
                                    <td class="px-4 py-3 text-surface-600 whitespace-nowrap">{{ $item->tglwo ? \Carbon\Carbon::parse($item->tglwo)->format('d/m/Y') : '-' }}</td>
                                @elseif ($jenis->value === 'history')
                                    <td class="px-4 py-3 text-surface-600 font-mono whitespace-nowrap">{{ $item->periode }}</td>
                                    <td class="px-4 py-3 font-semibold text-surface-900 whitespace-nowrap">{{ $item->nokontrak }}</td>
                                    <td class="px-4 py-3 text-surface-600 whitespace-nowrap">{{ $item->kdloc }}</td>
                                    <td class="px-4 py-3 text-right text-surface-900 font-mono tabular-nums whitespace-nowrap">Rp {{ number_format((float) $item->osmdlc, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-surface-600 font-mono tabular-nums whitespace-nowrap">{{ $item->haritgk }} hr</td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold bg-surface-100 text-surface-700">Kol {{ $item->kolek }}</span>
                                    </td>
                                @elseif ($jenis->value === 'jaminan')
                                    <td class="px-4 py-3 font-semibold text-surface-900 whitespace-nowrap">{{ $item->nokontrak }}</td>
                                    <td class="px-4 py-3 text-surface-600 font-mono whitespace-nowrap">{{ $item->noreg }}</td>
                                    <td class="px-4 py-3 text-surface-700 whitespace-nowrap">{{ $item->jnsjamin }}</td>
                                    <td class="px-4 py-3 text-right text-surface-600 font-mono tabular-nums whitespace-nowrap">Rp {{ number_format((float) ($item->nominalpasar ?? 0), 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-surface-900 font-mono font-semibold tabular-nums whitespace-nowrap">Rp {{ number_format((float) $item->nominallikuid, 0, ',', '.') }}</td>
                                @elseif ($jenis->value === 'kantor')
                                    <td class="px-4 py-3 font-semibold text-surface-900 font-mono whitespace-nowrap">{{ $item->kdloc }}</td>
                                    <td class="px-4 py-3 text-surface-800 font-medium whitespace-nowrap">{{ $item->nama }}</td>
                                    <td class="px-4 py-3 text-surface-600 truncate max-w-xs">{{ $item->alamat ?? '-' }}</td>
                                @elseif ($jenis->value === 'produk')
                                    <td class="px-4 py-3 font-semibold text-surface-900 font-mono whitespace-nowrap">{{ $item->kdprd }}</td>
                                    <td class="px-4 py-3 text-surface-800 font-medium whitespace-nowrap">{{ $item->nama }}</td>
                                    <td class="px-4 py-3 text-surface-600 whitespace-nowrap">{{ $item->pokpby }}{{ $item->akad ? ' — '.$item->akad->nama : '' }}</td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $item->aktif ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-surface-100 text-surface-600' }}">
                                            {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                @elseif ($jenis->value === 'jaminan_setup')
                                    <td class="px-4 py-3 font-semibold text-surface-900 font-mono whitespace-nowrap">{{ $item->kdjam }}</td>
                                    <td class="px-4 py-3 text-surface-800 font-medium whitespace-nowrap">{{ $item->ket }}</td>
                                    <td class="px-4 py-3 text-right text-surface-900 font-mono font-semibold whitespace-nowrap">{{ number_format((float) $item->bobot, 2, ',', '.') }}%</td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold bg-surface-100 text-surface-700">
                                            {{ $item->agunan()->count() }} jaminan
                                        </span>
                                    </td>
                                @endif
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('data.show', ['jenis' => $jenis->value, 'id' => $itemId]) }}"
                                           class="rounded p-1 text-surface-500 hover:bg-primary-50 hover:text-primary-600 transition"
                                           title="Lihat Detail">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('data.edit', ['jenis' => $jenis->value, 'id' => $itemId]) }}"
                                           class="rounded p-1 text-surface-500 hover:bg-amber-50 hover:text-amber-600 transition"
                                           title="Edit Data">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <button type="button"
                                                onclick="confirmDelete('{{ route('data.destroy', ['jenis' => $jenis->value, 'id' => $itemId]) }}', '{{ addslashes($itemName) }}')"
                                                class="rounded p-1 text-surface-500 hover:bg-rose-50 hover:text-rose-600 transition"
                                                title="Hapus Data">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-surface-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-10 w-10 text-surface-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                        Belum ada data {{ $jenis->label() }} yang ditemukan.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="border-t border-surface-200 px-4 py-3 bg-surface-50">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- Kolom wajib --}}
    <details class="mb-6">
        <summary class="cursor-pointer text-sm font-semibold text-surface-700 hover:text-primary-700">Kolom Template (klik untuk lihat)</summary>
        <div class="card mt-2">
            <ul class="flex flex-wrap gap-1.5">
                @foreach ($jenis->kolom() as $kolom)
                    @php $opsional = in_array($kolom, $jenis->kolomOpsional(), true); @endphp
                    <li class="rounded-lg px-2.5 py-1 text-xs font-medium {{ $opsional ? 'bg-surface-50 text-surface-500 ring-1 ring-inset ring-surface-200' : 'bg-surface-100 text-surface-600' }}">
                        {{ $kolom }}
                        @if ($opsional)
                            <span class="ml-1 text-[10px] uppercase tracking-wide">opsional</span>
                        @endif
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 text-xs text-surface-500">
                Tulis tanggal dengan format <span class="font-semibold">YYYYMMDD</span> (contoh 20260930)
                dan periode <span class="font-semibold">YYYYMM</span> (contoh 202609).
                Jangan mengubah atau menghapus baris judul kolom, dan baris kosong akan diabaikan saat diunggah.
                @if ($jenis->kolomOpsional() !== [])
                    Kolom opsional boleh dikosongkan; nilai kosong akan memakai nilai default sistem.
                @endif
            </p>
        </div>
    </details>

    {{-- Hidden form untuk delete confirmation --}}
    <form id="delete-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    {{-- Hidden form untuk truncate data pembiayaan --}}
    <form id="truncate-form" method="POST" style="display: none;">
        @csrf
    </form>

    <script>
        function confirmDelete(url, itemName) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus data ini?',
                    text: 'Data ' + itemName + ' akan dihapus permanen dari sistem.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('delete-form');
                        form.action = url;
                        form.submit();
                    }
                });
            } else {
                if (confirm('Yakin ingin menghapus ' + itemName + '?')) {
                    const form = document.getElementById('delete-form');
                    form.action = url;
                    form.submit();
                }
            }
        }

        // Konfirmasi truncate data pembiayaan
        document.addEventListener('DOMContentLoaded', () => {
            const truncateBtn = document.querySelector('[data-truncate-btn]');
            if (!truncateBtn) return;

            truncateBtn.addEventListener('click', () => {
                const action = truncateBtn.dataset.action;
                const msg = 'Semua data pembiayaan dan jaminan (agunan) akan dihapus permanen. History pembiayaan tetap tersimpan. Upload baru bisa dilakukan setelahnya. Lanjutkan?';

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Kosongkan data pembiayaan?',
                        text: msg,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Ya, Kosongkan',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById('truncate-form');
                            form.action = action;
                            form.submit();
                        }
                    });
                } else if (confirm(msg)) {
                    const form = document.getElementById('truncate-form');
                    form.action = action;
                    form.submit();
                }
            });
        });

        // Intercept form update agar ada dialog konfirmasi
        document.addEventListener('DOMContentLoaded', () => {
            const confirmForms = document.querySelectorAll('form[data-confirm]');
            confirmForms.forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (form.dataset.confirmed === 'true') return;
                    e.preventDefault();
                    const msg = form.getAttribute('data-confirm') || 'Simpan perubahan data ini?';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Konfirmasi',
                            text: msg,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#0f766e',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Ya, Simpan',
                            cancelButtonText: 'Batal',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.dataset.confirmed = 'true';
                                form.submit();
                            }
                        });
                    } else {
                        if (confirm(msg)) {
                            form.dataset.confirmed = 'true';
                            form.submit();
                        }
                    }
                });
            });
        });
    </script>
@endsection
