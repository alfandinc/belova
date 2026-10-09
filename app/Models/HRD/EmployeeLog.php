<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Audit trail of karyawan data (hrd_employee_logs). Callers take snapshot() of the employee before and after a
 * change and pass both to recordDiff(); one row is stored per changed field, as readable text.
 */
class EmployeeLog extends Model
{
    protected $table = 'hrd_employee_logs';

    protected $fillable = ['employee_id', 'aksi', 'kolom', 'sebelum', 'sesudah', 'user_id'];

    public const AKSI_LABEL = [
        'dibuat' => 'Karyawan ditambahkan',
        'ubah' => 'Ubah data',
        'kontrak' => 'Kontrak',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /** Readable state of the logged fields: label => text (null when empty). */
    public static function snapshot(Employee $employee): array
    {
        $employee->loadMissing(['positions', 'golGajiPokok', 'golTunjanganJabatan', 'user']);
        $date = fn($d) => $d ? $d->format('d/m/Y') : null;
        $file = fn($path) => $path ? basename($path) : null;

        $positions = $employee->positions
            ->sortByDesc(fn($p) => (int) $p->pivot->is_primary)
            ->map(fn($p) => $p->name . ($p->pivot->is_primary && $employee->positions->count() > 1 ? ' (utama)' : ''))
            ->implode(', ');
        $instagram = $employee->instagram;
        if (is_string($instagram)) {
            $instagram = json_decode($instagram, true) ?: [$instagram];
        }

        return [
            'Nama' => $employee->nama,
            'NIK' => $employee->nik,
            'No Induk' => $employee->no_induk,
            'Tempat lahir' => $employee->tempat_lahir,
            'Tanggal lahir' => $date($employee->tanggal_lahir),
            'Jenis kelamin' => ['L' => 'Laki-laki', 'P' => 'Perempuan'][strtoupper((string) $employee->jenis_kelamin)] ?? null,
            'Golongan darah' => $employee->gol_darah,
            'No HP' => $employee->no_hp,
            'No darurat' => $employee->no_darurat,
            'Nama kontak darurat' => $employee->darurat_nama,
            'Hubungan kontak darurat' => $employee->darurat_hubungan,
            'Email' => $employee->email,
            'Instagram' => $instagram ? implode(', ', array_filter((array) $instagram)) : null,
            'Pendidikan' => $employee->pendidikan,
            'Alamat' => $employee->alamat,
            'Perusahaan' => implode(', ', array_map(
                fn($nama) => $nama . ($nama === $employee->perusahaan && count($employee->daftarPerusahaan()) > 1 ? ' (utama)' : ''),
                $employee->daftarPerusahaan()
            )) ?: null,
            'Posisi' => $positions ?: null,
            'Status' => $employee->status ? ucfirst($employee->status) : null,
            'Tanggal masuk' => $date($employee->tanggal_masuk),
            'Kontrak berakhir' => $date($employee->kontrak_berakhir),
            'Tanggal nonaktif' => $date($employee->nonaktif_tanggal),
            'Alasan nonaktif' => $employee->nonaktif_alasan ? (Employee::NONAKTIF_ALASAN[$employee->nonaktif_alasan] ?? $employee->nonaktif_alasan) : null,
            'Keterangan nonaktif' => $employee->nonaktif_keterangan,
            'Kategori pegawai' => $employee->kategori_pegawai,
            'Gaji pokok' => $employee->golGajiPokok->golongan ?? null,
            'Tunjangan jabatan' => $employee->golTunjanganJabatan->golongan ?? null,
            'NPWP' => $employee->npwp,
            'No BPJS Kesehatan' => $employee->no_bpjs_kesehatan,
            'No BPJS Ketenagakerjaan' => $employee->no_bpjs_ketenagakerjaan,
            'Bank' => $employee->bank_nama,
            'No rekening' => $employee->bank_no_rekening,
            'Rekening atas nama' => $employee->bank_atas_nama,
            'Status pernikahan' => Employee::STATUS_PERNIKAHAN[$employee->status_pernikahan] ?? null,
            'Jumlah tanggungan' => $employee->jumlah_tanggungan,
            'Finger ID' => $employee->finger_id,
            'Akun login' => $employee->user ? $employee->user->name . ' (' . $employee->user->email . ')' : null,
            'Foto' => $file($employee->photo),
            'Dokumen CV' => $file($employee->doc_cv),
            'Dokumen KTP' => $file($employee->doc_ktp),
            'Dokumen kontrak' => $file($employee->doc_kontrak),
            'Dokumen pendukung' => $file($employee->doc_pendukung),
        ];
    }

    /** One row per field whose text differs between the two snapshots. */
    public static function recordDiff(int $employeeId, array $before, array $after, string $aksi = 'ubah'): void
    {
        foreach ($after as $kolom => $value) {
            $old = $before[$kolom] ?? null;
            if ((string) $old === (string) $value) {
                continue;
            }
            static::note($employeeId, $aksi, $kolom, $old, $value);
        }
    }

    public static function note(int $employeeId, string $aksi, ?string $kolom, ?string $sebelum, ?string $sesudah): void
    {
        static::create([
            'employee_id' => $employeeId,
            'aksi' => $aksi,
            'kolom' => $kolom,
            'sebelum' => $sebelum,
            'sesudah' => $sesudah,
            'user_id' => Auth::id(),
        ]);
    }
}
