<?php

namespace App\Http\Controllers;

use App\Enums\JenisUpload;
use App\Exports\TemplateUploadExport;
use App\Http\Requests\Data\DataRequest;
use App\Http\Requests\Data\HistoryRequest;
use App\Http\Requests\Data\JaminanRequest;
use App\Http\Requests\Data\JaminanSetupRequest;
use App\Http\Requests\Data\KantorRequest;
use App\Http\Requests\Data\PembiayaanRequest;
use App\Http\Requests\Data\ProdukRequest;
use App\Models\Agunan;
use App\Models\AuditLog;
use App\Models\CkpnPeriode;
use App\Models\HistoryPembiayaan;
use App\Models\Kantor;
use App\Models\KodeAkad;
use App\Models\Pembiayaan;
use App\Models\ProdukPembiayaan;
use App\Models\SetupJaminan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Menangani halaman data per jenis: upload, download template, dan input manual
 * dalam satu halaman bertab.
 */
class DataController extends Controller
{
    public function index(Request $request, JenisUpload $jenis): View
    {
        Gate::authorize('upload.'.$jenis->value);

        return view('dashboard.data.index', [
            'title' => $jenis->label(),
            'jenis' => $jenis,
            'tab' => in_array($request->query('tab'), ['upload', 'manual', 'data'], true) ? $request->query('tab') : 'data',
            'search' => $request->query('search', ''),
            'items' => $this->paginatedItems($jenis, $request->query('search')),
            'kodeAkads' => KodeAkad::options(),
            'produks' => $this->produks(),
            'setupJaminans' => $this->setupJaminans(),
        ]);
    }

    public function template(JenisUpload $jenis): BinaryFileResponse
    {
        Gate::authorize('upload.'.$jenis->value);

        return Excel::download(
            new TemplateUploadExport($jenis),
            'template_'.$jenis->value.'.xlsx',
        );
    }

    public function store(JenisUpload $jenis): RedirectResponse
    {
        Gate::authorize('upload.'.$jenis->value);

        $data = $this->formRequest($jenis)->validatedData();

        return match ($jenis) {
            JenisUpload::Pembiayaan => $this->storePembiayaan($data),
            JenisUpload::History => $this->storeHistory($data),
            JenisUpload::Jaminan => $this->storeJaminan($data),
            JenisUpload::Kantor => $this->storeKantor($data),
            JenisUpload::Produk => $this->storeProduk($data),
            JenisUpload::JaminanSetup => $this->storeJaminanSetup($data),
        };
    }

    public function show(JenisUpload $jenis, string $id): View
    {
        Gate::authorize('upload.'.$jenis->value);
        $item = $this->findItem($jenis, $id);

        return view('dashboard.data.show', [
            'title' => 'Detail '.$jenis->label(),
            'jenis' => $jenis,
            'item' => $item,
        ]);
    }

    public function edit(JenisUpload $jenis, string $id): View
    {
        Gate::authorize('upload.'.$jenis->value);
        $item = $this->findItem($jenis, $id);

        return view('dashboard.data.edit', [
            'title' => 'Edit '.$jenis->label(),
            'jenis' => $jenis,
            'item' => $item,
            // Kode akad yang sedang dipakai tetap ditawarkan walau nonaktif.
            'kodeAkads' => KodeAkad::options(data_get($item, 'pokpby')),
            'produks' => $this->produks(),
            'setupJaminans' => $this->setupJaminans(),
        ]);
    }

    public function update(JenisUpload $jenis, string $id): RedirectResponse
    {
        Gate::authorize('upload.'.$jenis->value);
        $item = $this->findItem($jenis, $id);
        $data = $this->formRequest($jenis)->validatedData();

        return match ($jenis) {
            JenisUpload::Pembiayaan => $this->updatePembiayaan($data, $item),
            JenisUpload::History => $this->updateHistory($data, $item),
            JenisUpload::Jaminan => $this->updateJaminan($data, $item),
            JenisUpload::Kantor => $this->updateKantor($data, $item),
            JenisUpload::Produk => $this->updateProduk($data, $item),
            JenisUpload::JaminanSetup => $this->updateJaminanSetup($data, $item),
        };
    }

    public function destroy(JenisUpload $jenis, string $id): RedirectResponse
    {
        Gate::authorize('upload.'.$jenis->value);
        $item = $this->findItem($jenis, $id);

        // History terikat periode: data periode terkunci/final tidak boleh dihapus.
        if ($item instanceof HistoryPembiayaan) {
            $this->assertPeriodeNotLocked($item->periode);
        }

        $item->delete();
        AuditLog::catat('delete', $item);

        return redirect()
            ->route('data.index', ['jenis' => $jenis, 'tab' => 'manual'])
            ->with('status', 'Data berhasil dihapus.');
    }

    /**
     * Kosongkan data pembiayaan beserta agunan agar upload baru tidak
     * bertabrakan dengan data lama. History pembiayaan sengaja tidak ikut
     * dihapus. Hanya untuk jenis Pembiayaan.
     */
    public function truncate(JenisUpload $jenis): RedirectResponse
    {
        Gate::authorize('upload.'.$jenis->value);

        abort_unless($jenis === JenisUpload::Pembiayaan, 404);

        $jumlah = Pembiayaan::query()->count();

        // MySQL menolak truncate saat FK aktif; SQLite (testing) tidak perlu.
        $mysql = DB::getDriverName() === 'mysql';

        if ($mysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        DB::table('agunan')->truncate();
        DB::table('pembiayaan')->truncate();

        if ($mysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        AuditLog::catat('truncate', null, [
            'tabel' => ['pembiayaan', 'agunan'],
            'jumlah_pembiayaan' => $jumlah,
        ]);

        return redirect()
            ->route('data.index', ['jenis' => $jenis, 'tab' => 'upload'])
            ->with('status', "Data pembiayaan dikosongkan ({$jumlah} rekening dihapus). Silakan upload data baru.");
    }

    /**
     * Daftar produk untuk dropdown kode produk, lengkap dengan kode akadnya
     * agar pilihan dapat disaring mengikuti kode akad terpilih.
     *
     * Dikembalikan sebagai array murni (bukan Eloquent Collection) agar aman
     * di-serialize/unserialize oleh session, cache, maupun cache view.
     *
     * @return list<array{kdprd: string, nama: string, pokpby: string}>
     */
    private function produks(): array
    {
        return ProdukPembiayaan::query()
            ->orderBy('kdprd')
            ->get(['kdprd', 'nama', 'pokpby'])
            ->map(fn ($produk): array => [
                'kdprd' => $produk->kdprd,
                'nama' => $produk->nama,
                'pokpby' => $produk->pokpby,
            ])
            ->all();
    }

    /**
     * Pilihan jenis jaminan untuk dropdown relasi agunan.
     *
     * @return array<string, string>
     */
    private function setupJaminans(): array
    {
        return SetupJaminan::query()
            ->orderBy('kdjam')
            ->pluck('ket', 'kdjam')
            ->all();
    }

    private function paginatedItems(JenisUpload $jenis, ?string $search = null): mixed
    {
        $search = trim((string) $search);

        return match ($jenis) {
            JenisUpload::Pembiayaan => Pembiayaan::query()
                ->with('akad')
                ->when($search !== '', function ($q) use ($search): void {
                    $q->where('nokontrak', 'like', "%{$search}%")
                        ->orWhere('nocif', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                })
                ->latest('nokontrak')
                ->paginate(15)
                ->withQueryString(),

            JenisUpload::History => HistoryPembiayaan::query()
                ->when($search !== '', function ($q) use ($search): void {
                    $q->where('nokontrak', 'like', "%{$search}%")
                        ->orWhere('periode', 'like', "%{$search}%");
                })
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),

            JenisUpload::Jaminan => Agunan::query()
                ->with('setupJaminan')
                ->when($search !== '', function ($q) use ($search): void {
                    $q->where('nokontrak', 'like', "%{$search}%")
                        ->orWhere('noreg', 'like', "%{$search}%")
                        ->orWhere('jnsjamin', 'like', "%{$search}%");
                })
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),

            JenisUpload::Kantor => Kantor::query()
                ->when($search !== '', function ($q) use ($search): void {
                    $q->where('kdloc', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                })
                ->orderBy('kdloc')
                ->paginate(15)
                ->withQueryString(),

            JenisUpload::Produk => ProdukPembiayaan::query()
                ->with('akad')
                ->when($search !== '', function ($q) use ($search): void {
                    $q->where('kdprd', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                })
                ->orderBy('kdprd')
                ->paginate(15)
                ->withQueryString(),

            JenisUpload::JaminanSetup => SetupJaminan::query()
                ->when($search !== '', function ($q) use ($search): void {
                    $q->where('kdjam', 'like', "%{$search}%")
                        ->orWhere('ket', 'like', "%{$search}%");
                })
                ->orderBy('kdjam')
                ->paginate(15)
                ->withQueryString(),
        };
    }

    private function findItem(JenisUpload $jenis, string $id): mixed
    {
        return match ($jenis) {
            JenisUpload::Pembiayaan => Pembiayaan::query()->where('nokontrak', $id)->firstOrFail(),
            JenisUpload::History => HistoryPembiayaan::query()->findOrFail($id),
            JenisUpload::Jaminan => Agunan::query()->findOrFail($id),
            JenisUpload::Kantor => Kantor::query()->where('kdloc', $id)->firstOrFail(),
            JenisUpload::Produk => ProdukPembiayaan::query()->where('kdprd', $id)->firstOrFail(),
            JenisUpload::JaminanSetup => SetupJaminan::query()->where('kdjam', $id)->firstOrFail(),
        };
    }

    /**
     * Validasi input memakai FormRequest sesuai jenis data.
     *
     * Jenis dibaca dari segmen URL, sehingga kelas FormRequest ditentukan di
     * sini alih-alih di-inject langsung oleh container. Resolusi lewat container
     * diperlukan agar FormRequest berisi data request saat ini dan tervalidasi.
     */
    private function formRequest(JenisUpload $jenis): DataRequest
    {
        $class = match ($jenis) {
            JenisUpload::Pembiayaan => PembiayaanRequest::class,
            JenisUpload::History => HistoryRequest::class,
            JenisUpload::Jaminan => JaminanRequest::class,
            JenisUpload::Kantor => KantorRequest::class,
            JenisUpload::Produk => ProdukRequest::class,
            JenisUpload::JaminanSetup => JaminanSetupRequest::class,
        };

        return app($class);
    }

    /**
     * Menolak penulisan data bila salah satu periode sudah terkunci atau final.
     *
     * Saat periode diubah, periode asal dan periode tujuan sama-sama diperiksa
     * agar data tidak dapat keluar dari periode yang sudah final.
     */
    private function assertPeriodeNotLocked(?string ...$periodes): void
    {
        foreach (array_unique(array_filter($periodes)) as $periode) {
            $periodeRecord = CkpnPeriode::query()->where('periode', $periode)->first();

            if ($periodeRecord === null) {
                continue;
            }

            abort_if(
                $periodeRecord->isLocked(),
                422,
                "Periode {$periode} sudah terkunci atau final.",
            );
        }
    }

    private function storePembiayaan(array $data): RedirectResponse
    {
        $model = Pembiayaan::query()->updateOrCreate(['nokontrak' => $data['nokontrak']], $data);
        AuditLog::catat('create', $model, $data);

        return redirect()
            ->route('data.index', ['jenis' => JenisUpload::Pembiayaan, 'tab' => 'manual'])
            ->with('status', "Data pembiayaan {$data['nokontrak']} tersimpan.");
    }

    private function storeHistory(array $data): RedirectResponse
    {
        $this->assertPeriodeNotLocked($data['periode']);

        $model = null;
        DB::transaction(function () use ($data, &$model): void {
            $model = HistoryPembiayaan::query()->updateOrCreate(
                ['nokontrak' => $data['nokontrak'], 'periode' => $data['periode']],
                $data,
            );
        });
        AuditLog::catat('create', $model, $data);

        return redirect()
            ->route('data.index', ['jenis' => JenisUpload::History, 'tab' => 'manual'])
            ->with('status', "History {$data['nokontrak']} periode {$data['periode']} tersimpan.");
    }

    private function storeJaminan(array $data): RedirectResponse
    {
        $model = Agunan::query()->updateOrCreate(
            ['nokontrak' => $data['nokontrak'], 'noreg' => $data['noreg'], 'urut' => $data['urut']],
            $data,
        );
        AuditLog::catat('create', $model, $data);

        return redirect()
            ->route('data.index', ['jenis' => JenisUpload::Jaminan, 'tab' => 'manual'])
            ->with('status', "Jaminan {$data['noreg']} tersimpan.");
    }

    private function storeKantor(array $data): RedirectResponse
    {
        $model = Kantor::query()->updateOrCreate(['kdloc' => $data['kdloc']], $data);
        AuditLog::catat('create', $model, $data);

        return redirect()
            ->route('data.index', ['jenis' => JenisUpload::Kantor, 'tab' => 'manual'])
            ->with('status', "Kantor {$data['kdloc']} tersimpan.");
    }

    private function storeProduk(array $data): RedirectResponse
    {
        $model = ProdukPembiayaan::query()->updateOrCreate(['kdprd' => $data['kdprd']], $data);
        AuditLog::catat('create', $model, $data);

        return redirect()
            ->route('data.index', ['jenis' => JenisUpload::Produk, 'tab' => 'manual'])
            ->with('status', "Produk {$data['kdprd']} tersimpan.");
    }

    private function storeJaminanSetup(array $data): RedirectResponse
    {
        $model = SetupJaminan::query()->updateOrCreate(['kdjam' => $data['kdjam']], $data);
        AuditLog::catat('create', $model, $data);

        return redirect()
            ->route('data.index', ['jenis' => JenisUpload::JaminanSetup, 'tab' => 'manual'])
            ->with('status', "Setup jaminan {$data['kdjam']} tersimpan.");
    }

    private function updatePembiayaan(array $data, Pembiayaan $pembiayaan): RedirectResponse
    {
        $pembiayaan->update($data);
        AuditLog::catat('update', $pembiayaan, $data);

        return redirect()
            ->route('data.index', ['jenis' => JenisUpload::Pembiayaan, 'tab' => 'manual'])
            ->with('status', "Data pembiayaan {$data['nokontrak']} diperbarui.");
    }

    private function updateHistory(array $data, HistoryPembiayaan $history): RedirectResponse
    {
        $this->assertPeriodeNotLocked($history->periode, $data['periode']);

        $history->update($data);
        AuditLog::catat('update', $history, $data);

        return redirect()
            ->route('data.index', ['jenis' => JenisUpload::History, 'tab' => 'manual'])
            ->with('status', "History {$data['nokontrak']} periode {$data['periode']} diperbarui.");
    }

    private function updateJaminan(array $data, Agunan $agunan): RedirectResponse
    {
        $agunan->update($data);
        AuditLog::catat('update', $agunan, $data);

        return redirect()
            ->route('data.index', ['jenis' => JenisUpload::Jaminan, 'tab' => 'manual'])
            ->with('status', "Jaminan {$data['noreg']} diperbarui.");
    }

    private function updateKantor(array $data, Kantor $kantor): RedirectResponse
    {
        $kantor->update($data);
        AuditLog::catat('update', $kantor, $data);

        return redirect()
            ->route('data.index', ['jenis' => JenisUpload::Kantor, 'tab' => 'manual'])
            ->with('status', "Kantor {$data['kdloc']} diperbarui.");
    }

    private function updateProduk(array $data, ProdukPembiayaan $produk): RedirectResponse
    {
        $produk->update($data);

        return redirect()
            ->route('data.index', ['jenis' => JenisUpload::Produk, 'tab' => 'manual'])
            ->with('status', "Produk {$data['kdprd']} diperbarui.");
    }

    private function updateJaminanSetup(array $data, SetupJaminan $setupJaminan): RedirectResponse
    {
        $setupJaminan->update($data);

        return redirect()
            ->route('data.index', ['jenis' => JenisUpload::JaminanSetup, 'tab' => 'manual'])
            ->with('status', "Setup jaminan {$data['kdjam']} diperbarui.");
    }
}
