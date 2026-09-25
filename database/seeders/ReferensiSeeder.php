<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferensiSeeder extends Seeder
{
    public function run(): void
    {
        // Jenis penggunaan pembiayaan.
        DB::table('jenis_penggunaan')->upsert([
            ['kode' => '1', 'nama' => 'Modal Kerja'],
            ['kode' => '2', 'nama' => 'Investasi'],
            ['kode' => '3', 'nama' => 'Konsumtif'],
        ], ['kode']);

        // Kode akad + skema atribut perhitungannya.
        DB::table('kode_akad')->upsert([
            ['pokpby' => '06', 'nama' => 'Murabahah', 'skema' => 'margin', 'aktif' => true],
            ['pokpby' => '03', 'nama' => 'Musyarakah', 'skema' => 'bagihasil', 'aktif' => true],
            ['pokpby' => '13', 'nama' => 'Ijarah Multijasa', 'skema' => 'ujrah', 'aktif' => true],
            ['pokpby' => '10', 'nama' => 'Ijarah Muntahiyah Bittamlik', 'skema' => 'sewa', 'aktif' => true],
            ['pokpby' => '09', 'nama' => 'Ijarah', 'skema' => 'sewa', 'aktif' => true],
            ['pokpby' => '11', 'nama' => 'Qardh', 'skema' => 'margin', 'aktif' => true],
        ], ['pokpby']);

        // Kode akad lama (kode teks) tidak dipakai lagi; bersihkan bila ada.
        DB::table('kode_akad')
            ->whereIn('pokpby', ['MURABAHAH', 'MULTIJASA', 'MUDHARABAH', 'MUSYARAKAH', 'IMBT', 'SALAM', 'ISTISHNA', 'QARDH'])
            ->delete();

        // Kantor contoh (sesuaikan dengan data aktual BPRS).
        DB::table('kantor')->upsert([
            ['kdloc' => '01', 'nama' => 'Kantor Pusat', 'alamat' => ''],
            ['kdloc' => '02', 'nama' => 'Kantor Cabang 1', 'alamat' => ''],
            ['kdloc' => '03', 'nama' => 'Kantor Cabang 2', 'alamat' => ''],
        ], ['kdloc']);

        // Produk pembiayaan contoh, pokpby mengacu ke tabel kode_akad.
        DB::table('produk_pembiayaan')->upsert([
            ['kdprd' => 'P001', 'nama' => 'Produk 001', 'pokpby' => '06', 'aktif' => true],
            ['kdprd' => 'P002', 'nama' => 'Produk 002', 'pokpby' => '03', 'aktif' => true],
            ['kdprd' => 'P003', 'nama' => 'Produk 003', 'pokpby' => '11', 'aktif' => true],
        ], ['kdprd']);

        // Bucket default: 13 numerik + PO/WO.
        $buckets = [];
        for ($i = 1; $i <= 13; $i++) {
            $buckets[] = [
                'kode' => (string) $i,
                'label' => "Bucket {$i}",
                'deskripsi' => $i === 1 ? 'Lancar (tanpa tunggakan)' : "Bucket {$i}",
                'urutan' => $i,
                'is_default' => true,
            ];
        }
        $buckets[] = ['kode' => 'PO', 'label' => 'Paid Off', 'deskripsi' => 'Lunas — keluar dari snapshot', 'urutan' => 98, 'is_default' => true];
        $buckets[] = ['kode' => 'WO', 'label' => 'Write Off', 'deskripsi' => 'Hapus buku (stsacc = W)', 'urutan' => 99, 'is_default' => true];

        DB::table('ckpn_bucket')->upsert($buckets, ['kode']);
    }
}
