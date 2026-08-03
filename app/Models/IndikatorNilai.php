<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndikatorNilai extends Model
{
    protected $table = 'indikator_nilais';

    protected $fillable = [
        'periode_id',
        'baris_id',
        'kolom_id',
        'nilai',
        'nilai_numerik',
    ];

    protected $casts = [
        'nilai' => 'string',
        'nilai_numerik' => 'decimal:4',
    ];

    public function periode(): BelongsTo
    {
        return $this->belongsTo(IndikatorPeriode::class, 'periode_id');
    }

    public function baris(): BelongsTo
    {
        return $this->belongsTo(IndikatorBaris::class, 'baris_id');
    }

    public function kolom(): BelongsTo
    {
        return $this->belongsTo(IndikatorKolom::class, 'kolom_id');
    }

    /**
     * Set nilai mentah (string, verbatim dari excel) dan otomatis hitung nilai_numerik-nya.
     * `nilai` tidak pernah diubah/dirapikan -- selalu apa adanya dari sumbernya.
     */
    public function setNilaiMentah(?string $rawValue): static
    {
        $this->nilai = $rawValue;
        $this->nilai_numerik = self::parseAngkaIndonesia($rawValue);

        return $this;
    }

    /**
     * Parse format angka gaya BPS/Indonesia ke float, tanpa menyentuh kolom `nilai` aslinya.
     * Aturan:
     *  - "-" atau kosong -> null (bukan 0, karena artinya "tidak ada data")
     *  - "225.182"        -> 225182   (titik = pemisah ribuan)
     *  - "225,18"         -> 225.18   (koma = pemisah desimal)
     *  - "225.182,5"      -> 225182.5 (titik ribuan + koma desimal, gabungan)
     */
    public static function parseAngkaIndonesia(?string $rawValue): ?string
    {
        if ($rawValue === null) {
            return null;
        }

        $value = trim($rawValue);

        if ($value === '' || $value === '-') {
            return null;
        }

        // Hilangkan karakter selain digit, titik, koma, minus
        $value = preg_replace('/[^0-9.,\-]/', '', $value);

        if ($value === '' || $value === '-') {
            return null;
        }

        $adaTitik = str_contains($value, '.');
        $adaKoma = str_contains($value, ',');

        if ($adaTitik && $adaKoma) {
            // Titik = ribuan, koma = desimal -> buang titik, ganti koma jadi titik
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif ($adaKoma && !$adaTitik) {
            // Hanya koma -> anggap desimal
            $value = str_replace(',', '.', $value);
        } elseif ($adaTitik && !$adaKoma) {
            // Hanya titik -> gaya BPS: ini pemisah ribuan (bukan desimal), buang semua titik
            $value = str_replace('.', '', $value);
        }

        return is_numeric($value) ? $value : null;
    }
}
