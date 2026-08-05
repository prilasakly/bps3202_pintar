<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserExcelImporter
{
    /**
     * Kolom (1-based, A=1) sesuai urutan resmi di UserExcelTemplateGenerator.
     * Kalau urutan header di template diubah, sesuaikan juga konstanta ini.
     */
    private const KOLOM_NAMA = 1;

    private const KOLOM_EMAIL = 2;

    private const KOLOM_PASSWORD = 3;

    private const KOLOM_NIP_LAMA = 4;

    private const KOLOM_NIP_BARU = 5;

    private const KOLOM_GOLONGAN = 6;

    private const KOLOM_JABATAN = 7;

    private const KOLOM_ROLE = 8;

    /**
     * @return array{
     *     status: string,
     *     pesan: string,
     *     jumlah_baris: int,
     *     jumlah_berhasil: int,
     *     jumlah_gagal: int,
     *     berhasil: array<int, array{baris:int, nama:string, email:string, aksi:string}>,
     *     errors: array<int, array{baris:int, pesan:string}>,
     * }
     */
    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('Template User') ?? $spreadsheet->getSheet(0);

        $rolesBySlug = Role::all()->keyBy(fn (Role $r) => mb_strtolower($r->slug));

        $highestRow = $sheet->getHighestDataRow();
        $berhasil = [];
        $errors = [];
        $jumlahBaris = 0;

        // Data dimulai baris ke-2 (baris 1 = header kolom, lihat UserExcelTemplateGenerator).
        for ($row = 2; $row <= $highestRow; $row++) {
            $nama = trim((string) $sheet->getCell([self::KOLOM_NAMA, $row])->getValue());
            $email = trim((string) $sheet->getCell([self::KOLOM_EMAIL, $row])->getValue());

            // Baris kosong total -- lewati diam-diam (bukan dianggap error), supaya baris
            // pemisah/format sisa di file tidak bikin bingung pengguna.
            if ($nama === '' && $email === '') {
                continue;
            }

            $jumlahBaris++;

            try {
                $aksi = $this->prosesBaris($row, $nama, $email, $sheet, $rolesBySlug);
                $berhasil[] = ['baris' => $row, 'nama' => $nama, 'email' => $email, 'aksi' => $aksi];
            } catch (InvalidArgumentException $e) {
                $errors[] = ['baris' => $row, 'pesan' => $e->getMessage()];
            }
        }

        $jumlahBerhasil = count($berhasil);
        $jumlahGagal = count($errors);

        $status = match (true) {
            $jumlahBaris === 0 => 'gagal',
            $jumlahGagal === 0 => 'sukses',
            $jumlahBerhasil === 0 => 'gagal',
            default => 'sebagian',
        };

        $pesan = match ($status) {
            'gagal' => $jumlahBaris === 0
                ? 'Tidak ada baris data ditemukan. Pastikan data diisi mulai baris ke-2 pada sheet "Template User".'
                : "Semua {$jumlahBaris} baris gagal diimport. Periksa daftar galat di bawah.",
            'sebagian' => "{$jumlahBerhasil} dari {$jumlahBaris} baris berhasil diimport, {$jumlahGagal} baris gagal. Periksa daftar galat di bawah.",
            default => "Import berhasil: {$jumlahBerhasil} user diproses.",
        };

        return [
            'status' => $status,
            'pesan' => $pesan,
            'jumlah_baris' => $jumlahBaris,
            'jumlah_berhasil' => $jumlahBerhasil,
            'jumlah_gagal' => $jumlahGagal,
            'berhasil' => $berhasil,
            'errors' => $errors,
        ];
    }

    /**
     * Proses satu baris data user. Melempar InvalidArgumentException (ditangkap
     * oleh pemanggil) kalau baris ini tidak valid -- baris lain tetap lanjut diproses.
     *
     * @param  Collection<string, Role>  $rolesBySlug  key = slug huruf kecil
     * @return string 'ditambahkan' atau 'diperbarui'
     */
    private function prosesBaris(int $row, string $nama, string $email, Worksheet $sheet, Collection $rolesBySlug): string
    {
        if ($nama === '' || $email === '') {
            throw new InvalidArgumentException('Kolom Nama dan Email wajib diisi.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Format email \"{$email}\" tidak valid.");
        }

        $password = trim((string) $sheet->getCell([self::KOLOM_PASSWORD, $row])->getValue());
        $nipLama = trim((string) $sheet->getCell([self::KOLOM_NIP_LAMA, $row])->getValue()) ?: null;
        $nipBaru = trim((string) $sheet->getCell([self::KOLOM_NIP_BARU, $row])->getValue()) ?: null;
        $golongan = trim((string) $sheet->getCell([self::KOLOM_GOLONGAN, $row])->getValue()) ?: null;
        $jabatan = trim((string) $sheet->getCell([self::KOLOM_JABATAN, $row])->getValue()) ?: null;
        $roleRaw = trim((string) $sheet->getCell([self::KOLOM_ROLE, $row])->getValue());

        if ($password !== '' && mb_strlen($password) < 8) {
            throw new InvalidArgumentException('Kata sandi minimal 8 karakter.');
        }

        // Cocokkan slug role (dipisah koma) ke tabel roles. Kalau ada SATU SAJA slug yang
        // tidak dikenal, seluruh baris digagalkan (all-or-nothing) -- supaya tidak ada user
        // yang diam-diam ke-assign role yang salah ketik / kosong sebagian.
        [$roleIds, $roleTidakDikenal] = $this->cocokkanRole($roleRaw, $rolesBySlug);

        if (! empty($roleTidakDikenal)) {
            throw new InvalidArgumentException(
                'Role tidak dikenal: '.implode(', ', $roleTidakDikenal).'. Lihat sheet "Daftar Role" pada file template.'
            );
        }

        $existing = User::where('email', $email)->first();

        if (! $existing && $password === '') {
            throw new InvalidArgumentException('Kolom Password wajib diisi untuk user baru (email belum terdaftar).');
        }

        return DB::transaction(function () use ($existing, $nama, $email, $password, $nipLama, $nipBaru, $golongan, $jabatan, $roleRaw, $roleIds) {
            $data = [
                'name' => $nama,
                'email' => $email,
                'nip_lama' => $nipLama,
                'nip_baru' => $nipBaru,
                'golongan' => $golongan,
                'jabatan' => $jabatan,
            ];

            if ($password !== '') {
                $data['password'] = Hash::make($password);
            }

            if ($existing) {
                $existing->update($data);
                $user = $existing;
                $aksi = 'diperbarui';
            } else {
                $user = User::create($data);
                $aksi = 'ditambahkan';
            }

            // Role cuma disinkron kalau kolom Role diisi di baris ini. Kolom Role kosong
            // dibiarkan (tidak menghapus role existing) -- supaya import ulang yang cuma
            // dipakai untuk update data kepegawaian tidak diam-diam mencopot role user.
            if ($roleRaw !== '') {
                $user->roles()->sync($roleIds);
            }

            return $aksi;
        });
    }

    /**
     * @param  Collection<string, Role>  $rolesBySlug
     * @return array{0: array<int, int>, 1: array<int, string>} [$roleIds, $slugTidakDikenal]
     */
    private function cocokkanRole(string $roleRaw, Collection $rolesBySlug): array
    {
        $roleIds = [];
        $tidakDikenal = [];

        if ($roleRaw === '') {
            return [$roleIds, $tidakDikenal];
        }

        foreach (explode(',', $roleRaw) as $slugMentah) {
            $slug = mb_strtolower(trim($slugMentah));

            if ($slug === '') {
                continue;
            }

            if ($rolesBySlug->has($slug)) {
                $roleIds[] = $rolesBySlug->get($slug)->id;
            } else {
                $tidakDikenal[] = trim($slugMentah);
            }
        }

        return [$roleIds, $tidakDikenal];
    }
}