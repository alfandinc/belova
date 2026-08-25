<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinanceDummySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $userId = DB::table('users')->orderBy('id')->value('id');

        $accounts = [
            [
                'kode_akun' => '1000',
                'nama_akun' => 'Kas & Bank',
                'tipe_akun' => 'Asset',
                'parent_kode' => null,
                'is_active' => true,
            ],
            [
                'kode_akun' => '1100',
                'nama_akun' => 'Kas Kecil',
                'tipe_akun' => 'Asset',
                'parent_kode' => '1000',
                'is_active' => true,
            ],
            [
                'kode_akun' => '1200',
                'nama_akun' => 'Bank BCA',
                'tipe_akun' => 'Asset',
                'parent_kode' => '1000',
                'is_active' => true,
            ],
            [
                'kode_akun' => '4000',
                'nama_akun' => 'Pendapatan Jasa',
                'tipe_akun' => 'Revenue',
                'parent_kode' => null,
                'is_active' => true,
            ],
            [
                'kode_akun' => '4100',
                'nama_akun' => 'Pendapatan Rawat Jalan',
                'tipe_akun' => 'Revenue',
                'parent_kode' => '4000',
                'is_active' => true,
            ],
            [
                'kode_akun' => '5000',
                'nama_akun' => 'Beban Operasional',
                'tipe_akun' => 'Expense',
                'parent_kode' => null,
                'is_active' => true,
            ],
            [
                'kode_akun' => '5100',
                'nama_akun' => 'Beban ATK',
                'tipe_akun' => 'Expense',
                'parent_kode' => '5000',
                'is_active' => true,
            ],
            [
                'kode_akun' => '5200',
                'nama_akun' => 'Beban Listrik',
                'tipe_akun' => 'Expense',
                'parent_kode' => '5000',
                'is_active' => true,
            ],
            [
                'kode_akun' => '2000',
                'nama_akun' => 'Hutang Usaha',
                'tipe_akun' => 'Liability',
                'parent_kode' => null,
                'is_active' => true,
            ],
            [
                'kode_akun' => '3000',
                'nama_akun' => 'Modal Pemilik',
                'tipe_akun' => 'Equity',
                'parent_kode' => null,
                'is_active' => true,
            ],
        ];

        $accountIds = [];

        foreach ($accounts as $account) {
            $parentId = null;
            $level = 0;

            if ($account['parent_kode']) {
                $parentId = $accountIds[$account['parent_kode']] ?? DB::table('finance_akun')
                    ->where('kode_akun', $account['parent_kode'])
                    ->value('id');

                $parentLevel = $parentId
                    ? (int) DB::table('finance_akun')->where('id', $parentId)->value('level')
                    : 0;

                $level = $parentLevel + 1;
            }

            DB::table('finance_akun')->updateOrInsert(
                ['kode_akun' => $account['kode_akun']],
                [
                    'parent_id' => $parentId,
                    'nama_akun' => $account['nama_akun'],
                    'tipe_akun' => $account['tipe_akun'],
                    'level' => $level,
                    'is_active' => $account['is_active'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $accountIds[$account['kode_akun']] = DB::table('finance_akun')
                ->where('kode_akun', $account['kode_akun'])
                ->value('id');
        }

        $journals = [
            [
                'no_jurnal' => 'JU-DUMMY-0001',
                'tanggal' => now()->startOfMonth()->format('Y-m-d'),
                'ref_id' => 'DUMMY-SETOR-MODAL',
                'keterangan' => 'Setoran modal awal kas kecil',
                'lines' => [
                    ['kode_akun' => '1100', 'debet' => 5000000, 'kredit' => 0, 'pos' => 'D'],
                    ['kode_akun' => '3000', 'debet' => 0, 'kredit' => 5000000, 'pos' => 'K'],
                ],
            ],
            [
                'no_jurnal' => 'JU-DUMMY-0002',
                'tanggal' => now()->startOfMonth()->addDays(2)->format('Y-m-d'),
                'ref_id' => 'DUMMY-PENDAPATAN-01',
                'keterangan' => 'Penerimaan pendapatan rawat jalan tunai',
                'lines' => [
                    ['kode_akun' => '1100', 'debet' => 1250000, 'kredit' => 0, 'pos' => 'D'],
                    ['kode_akun' => '4100', 'debet' => 0, 'kredit' => 1250000, 'pos' => 'K'],
                ],
            ],
            [
                'no_jurnal' => 'JU-DUMMY-0003',
                'tanggal' => now()->startOfMonth()->addDays(4)->format('Y-m-d'),
                'ref_id' => 'DUMMY-ATK-01',
                'keterangan' => 'Pembelian ATK dibayar tunai',
                'lines' => [
                    ['kode_akun' => '5100', 'debet' => 350000, 'kredit' => 0, 'pos' => 'D'],
                    ['kode_akun' => '1100', 'debet' => 0, 'kredit' => 350000, 'pos' => 'K'],
                ],
            ],
            [
                'no_jurnal' => 'JU-DUMMY-0004',
                'tanggal' => now()->startOfMonth()->addDays(6)->format('Y-m-d'),
                'ref_id' => 'DUMMY-LISTRIK-01',
                'keterangan' => 'Tagihan listrik bulan berjalan belum dibayar',
                'lines' => [
                    ['kode_akun' => '5200', 'debet' => 875000, 'kredit' => 0, 'pos' => 'D'],
                    ['kode_akun' => '2000', 'debet' => 0, 'kredit' => 875000, 'pos' => 'K'],
                ],
            ],
        ];

        foreach ($journals as $journal) {
            DB::table('finance_jurnal')->where('no_jurnal', $journal['no_jurnal'])->delete();

            foreach ($journal['lines'] as $line) {
                DB::table('finance_jurnal')->insert([
                    'no_jurnal' => $journal['no_jurnal'],
                    'tanggal' => $journal['tanggal'],
                    'akun_id' => $accountIds[$line['kode_akun']] ?? null,
                    'debet' => $line['debet'],
                    'kredit' => $line['kredit'],
                    'keterangan' => $journal['keterangan'],
                    'ref_id' => $journal['ref_id'],
                    'user_id' => $userId,
                    'pos' => $line['pos'],
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]);
            }
        }
    }
}
