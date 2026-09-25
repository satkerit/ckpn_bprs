<?php

namespace App\Http\Controllers;

use App\Enums\StatusRun;
use App\Models\CkpnPeriode;
use App\Models\CkpnRun;
use App\Models\Pembiayaan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('dashboard.view');

        $latestCompletedRun = CkpnRun::query()
            ->withCount('hasil')
            ->where('status', StatusRun::Done)
            ->latest('periode')
            ->latest('finished_at')
            ->first();

        return view('dashboard.index', [
            'totalPembiayaan' => Pembiayaan::query()->count(),
            'totalPeriode' => CkpnPeriode::query()->count(),
            'periodeTerakhir' => CkpnPeriode::query()->latest('periode')->first(),
            'latestCompletedRun' => $latestCompletedRun,
        ]);
    }
}
