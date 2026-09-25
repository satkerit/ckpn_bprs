<?php

namespace App\Livewire\Ckpn;

use App\Models\CkpnHasil;
use App\Models\CkpnRingkasanKantor;
use App\Models\CkpnRun;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public ?int $runId = null;

    public function pilihRun(int $runId): void
    {
        Gate::authorize('ckpn.view');
        abort_unless(CkpnRun::query()->whereKey($runId)->exists(), 404);
        $this->runId = $runId;
    }

    public function ringkasKantor(): void
    {
        Gate::authorize('ckpn.calculate');
        $run = CkpnRun::query()->findOrFail($this->runId);

        $rows = CkpnHasil::query()
            ->selectRaw('
                ? as ckpn_run_id,
                ? as periode,
                kdloc,
                COUNT(*) as jumlah_rekening,
                COALESCE(SUM(ead), 0) as total_ead,
                COALESCE(SUM(ckpn_psak414), 0) as total_ckpn,
                COALESCE(SUM(ppka_wajib), 0) as total_ppka
            ', [$run->id, $run->periode])
            ->where('ckpn_run_id', $run->id)
            ->groupBy('kdloc')
            ->get()
            ->map(fn ($item): array => [
                'ckpn_run_id' => $item->ckpn_run_id,
                'periode' => $item->periode,
                'kdloc' => $item->kdloc,
                'jumlah_rekening' => (int) $item->jumlah_rekening,
                'total_ead' => (float) $item->total_ead,
                'total_ckpn' => (float) $item->total_ckpn,
                'total_ppka' => (float) $item->total_ppka,
            ])
            ->all();

        DB::transaction(function () use ($run, $rows): void {
            CkpnRingkasanKantor::query()->where('ckpn_run_id', $run->id)->delete();
            if ($rows !== []) {
                CkpnRingkasanKantor::query()->insert($rows);
            }
        });
    }

    public function render(): View
    {
        Gate::authorize('ckpn.view');
        $run = $this->runId ? CkpnRun::query()->find($this->runId) : CkpnRun::query()->latest()->first();

        return view('livewire.ckpn.index', [
            'runs' => CkpnRun::query()->latest()->limit(20)->get(),
            'run' => $run,
            'hasil' => $run ? CkpnHasil::query()->where('ckpn_run_id', $run->id)->latest()->limit(100)->get() : collect(),
            'ringkasan' => $run ? CkpnRingkasanKantor::query()->where('ckpn_run_id', $run->id)->get() : collect(),
        ]);
    }
}
