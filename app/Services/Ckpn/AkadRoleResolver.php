<?php

namespace App\Services\Ckpn;

use App\Enums\SyaratMasukCkpn;
use App\Models\AkadRole;
use App\Models\HistoryPembiayaan;

/**
 * Sumber tunggal aturan peran CKPN per kode akad (pokpby).
 *
 * Dipakai bersama oleh EadCalculator (komposisi EAD), CkpnCalculator
 * (populasi yang dihitung), dan RollRateCalculator (snapshot seluruh metode PD:
 * Netflow dan Migration), sehingga ketiganya membaca definisi yang sama.
 */
class AkadRoleResolver
{
    /** @var array<string, AkadRole>|null */
    private ?array $roles = null;

    /**
     * Kolom history yang dijumlahkan menjadi EAD untuk kode akad ini.
     *
     * @return array<int, string>
     */
    public function eadColumns(?string $pokpby): array
    {
        $role = $this->role($pokpby);

        if ($role !== null) {
            return $role->eadColumns();
        }

        return $this->fallbackColumns($pokpby);
    }

    public function syaratMasuk(?string $pokpby): SyaratMasukCkpn
    {
        $role = $this->role($pokpby);

        if ($role !== null) {
            return $role->syarat_masuk;
        }

        $outOfScope = config('ckpn.perhitungan.akad_out_of_scope_lancar', ['03', '10']);

        return in_array((string) $pokpby, $outOfScope, true)
            ? SyaratMasukCkpn::JatuhTempo
            : SyaratMasukCkpn::Selalu;
    }

    /**
     * Apakah rekening masuk populasi CKPN / snapshot seluruh metode PD.
     *
     * Syarat `jatuh_tempo` membandingkan tglexp dengan akhir bulan periode
     * baris history itu sendiri, sehingga snapshot historis tetap konsisten.
     */
    public function masukPerhitungan(?string $pokpby, HistoryPembiayaan $history): bool
    {
        return match ($this->syaratMasuk($pokpby)) {
            SyaratMasukCkpn::Selalu => true,
            SyaratMasukCkpn::AdaTunggakan => (float) $history->tgkmdl > 0,
            SyaratMasukCkpn::JatuhTempo => $this->sudahJatuhTempo($history),
        };
    }

    public function sudahJatuhTempo(HistoryPembiayaan $history): bool
    {
        if ((int) $history->haritgk > 0 || (float) $history->tgkmdl > 0 || (float) $history->tgkmgn > 0) {
            return true;
        }

        $tglexp = (string) $history->tglexp;

        if (strlen($tglexp) !== 8 || ! ctype_digit($tglexp)) {
            return false;
        }

        $periode = (string) $history->periode;

        if (strlen($periode) !== 6 || ! ctype_digit($periode)) {
            return false;
        }

        $akhirBulan = date('Ymt', strtotime(substr($periode, 0, 4).'-'.substr($periode, 4, 2).'-01'));

        return $tglexp <= $akhirBulan;
    }

    private function role(?string $pokpby): ?AkadRole
    {
        if ($pokpby === null || $pokpby === '') {
            return null;
        }

        $this->roles ??= AkadRole::query()->get()->keyBy('pokpby')->all();

        return $this->roles[$pokpby] ?? null;
    }

    /**
     * Fallback bila role belum diatur di setup: samakan dengan config lama
     * agar hasil perhitungan tidak berubah sebelum user mengatur role.
     *
     * @return array<int, string>
     */
    private function fallbackColumns(?string $pokpby): array
    {
        $basis = config('ckpn.perhitungan.akad_basis_tunggakan.'.(string) $pokpby)
            ?? config('ckpn.perhitungan.akad_basis_default', 'pokok_margin');

        return match ($basis) {
            'margin' => ['osmdlc', 'tgkmgn'],
            'kondisional' => ['osmdlc', 'tgkmdl', 'tgkmgn'],
            default => ['osmdlc', 'tgkmdl', 'tgkmgn'],
        };
    }
}
