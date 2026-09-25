<?php

namespace App\Services\Ckpn;

use App\Models\HistoryPembiayaan;
use App\Models\Pembiayaan;

class EadCalculator
{
    public function __construct(private readonly AkadRoleResolver $roles) {}

    public function calculate(Pembiayaan $pembiayaan, ?HistoryPembiayaan $history = null): float
    {
        $history ??= $pembiayaan->histories()->latest('periode')->first();

        if ($history === null) {
            return 0.0;
        }

        if (! $this->roles->masukPerhitungan($pembiayaan->pokpby, $history)) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($this->roles->eadColumns($pembiayaan->pokpby) as $column) {
            $total += max(0.0, (float) $history->{$column});
        }

        return round($total, 2);
    }
}
