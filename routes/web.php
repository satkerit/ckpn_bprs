<?php

use App\Enums\JenisUpload;
use App\Exports\CkpnHasilExport;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\UploadController;
use App\Livewire\Admin\Audit as AuditIndex;
use App\Livewire\Admin\Permissions as PermissionsIndex;
use App\Livewire\Admin\Roles as RolesIndex;
use App\Livewire\Admin\Users as UsersIndex;
use App\Livewire\Ckpn\Index as CkpnIndex;
use App\Livewire\Lgd\Index as LgdIndex;
use App\Livewire\Master\KodeAkad as KodeAkadMaster;
use App\Livewire\Pd\Migration as PdMigration;
use App\Livewire\Pd\Netflow as PdNetflow;
use App\Livewire\Periode\Index as PeriodeIndex;
use App\Livewire\Setup\CkpnRole as CkpnRoleSetup;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/periode', PeriodeIndex::class)->name('periode.index');

    // Halaman data memakai satu pola URL /data/{jenis}/... sehingga jenis data
    // dibaca sebagai segmen URL dan di-bind otomatis ke enum JenisUpload.
    Route::prefix('data')->name('data.')->middleware('throttle:60,1')->group(function (): void {
        Route::redirect('/', '/data/pembiayaan');

        Route::whereIn('jenis', array_column(JenisUpload::cases(), 'value'))
            ->group(function (): void {
                Route::get('{jenis}', [DataController::class, 'index'])->name('index');
                Route::get('{jenis}/template', [DataController::class, 'template'])->name('template');
                Route::post('{jenis}/store', [DataController::class, 'store'])->name('store');
                Route::post('{jenis}/truncate', [DataController::class, 'truncate'])->name('truncate');
                Route::get('{jenis}/{id}', [DataController::class, 'show'])->name('show');
                Route::get('{jenis}/{id}/edit', [DataController::class, 'edit'])->name('edit');
                Route::put('{jenis}/{id}', [DataController::class, 'update'])->name('update');
                Route::delete('{jenis}/{id}', [DataController::class, 'destroy'])->name('destroy');
            });
    });

    Route::get('/upload/riwayat', [UploadController::class, 'riwayat'])->name('upload.riwayat')->middleware('throttle:60,1');
    Route::get('/upload/riwayat/{batch}/error', [UploadController::class, 'errorReport'])->name('upload.error')->middleware('throttle:60,1');
    Route::get('/upload/batch/{batch}/status', [UploadController::class, 'status'])->name('upload.status')->middleware('throttle:180,1');
    Route::get('/upload', fn () => redirect()->route('data.index', ['jenis' => JenisUpload::Pembiayaan]))->name('upload.index')->middleware('throttle:60,1');
    Route::post('/upload', [UploadController::class, 'store'])->name('upload.store')->middleware('throttle:60,1');

    Route::get('/pd/netflow', PdNetflow::class)->name('pd.netflow');
    Route::get('/pd/migration', PdMigration::class)->name('pd.migration');
    Route::get('/lgd', LgdIndex::class)->name('lgd.index');
    Route::get('/ckpn', CkpnIndex::class)->name('ckpn.index');
    Route::get('/master/kode-akad', KodeAkadMaster::class)->name('master.kode-akad');
    Route::get('/setup/ckpn-role', CkpnRoleSetup::class)->name('setup.ckpn-role');

    Route::get('/admin/users', UsersIndex::class)->name('admin.users');
    Route::get('/admin/roles', RolesIndex::class)->name('admin.roles');
    Route::get('/admin/permissions', PermissionsIndex::class)->name('admin.permissions');
    Route::get('/admin/audit', AuditIndex::class)->name('admin.audit');
    Route::get('/ckpn/export/{runId?}', function (?int $runId = null) {
        Gate::authorize('ckpn.export');

        return Excel::download(new CkpnHasilExport($runId), 'hasil_ckpn.xlsx');
    })->middleware('throttle:10,1')->name('ckpn.export');
});
