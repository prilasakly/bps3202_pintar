<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use Illuminate\Database\Seeder;

class KecamatanSeeder extends Seeder
{
    /**
     * Urutan dan penulisan nama persis mengikuti urutan baris pada file BPS
     * (Jarak ke Ibukota Kabupaten / Luas Wilayah Menurut Kecamatan), supaya
     * saat import excel nanti nama baris bisa langsung match ke tabel ini.
     */
    public function run(): void
    {
        $kecamatan = [
            'CIEMAS', 'CIRACAP', 'WALURAN', 'SURADE', 'CIBITUNG', 'JAMPANG KULON',
            'CIMANGGU', 'KALI BUNDER', 'TEGAL BULEUD', 'CIDOLOG', 'SAGARANTEN',
            'CIDADAP', 'CURUGKEMBAR', 'PABUARAN', 'LENGKONG', 'PALABUHANRATU',
            'SIMPENAN', 'WARUNG KIARA', 'BANTARGADUNG', 'JAMPANG TENGAH', 'PURABAYA',
            'CIKEMBAR', 'NYALINDUNG', 'GEGER BITUNG', 'SUKARAJA', 'KEBONPEDES',
            'CIREUNGHAS', 'SUKALARANG', 'SUKABUMI', 'KADUDAMPIT', 'CISAAT',
            'GUNUNGGURUH', 'CIBADAK', 'CICANTAYAN', 'CARINGIN', 'NAGRAK',
            'CIAMBAR', 'CICURUG', 'CIDAHU', 'PARAKAN SALAK', 'PARUNG KUDA',
            'BOJONG GENTENG', 'KALAPA NUNGGAL', 'CIKIDANG', 'CISOLOK', 'CIKAKAK',
            'KABANDUNGAN',
        ];

        foreach ($kecamatan as $urutan => $nama) {
            Kecamatan::updateOrCreate(
                ['nama' => $nama],
                ['urutan' => $urutan + 1]
            );
        }
    }
}
