<?php
/**
 * Cvss - Kalkulator CVSS v3.1 Base Score (sisi server).
 *
 * Skor dari klien (JavaScript) hanya untuk tampilan real-time;
 * server SELALU menghitung ulang dari metrik agar tidak bisa dimanipulasi.
 * Referensi: https://www.first.org/cvss/v3.1/specification-document
 */

declare(strict_types=1);

final class Cvss
{
    /** Definisi metrik: kode => [label, [nilai => label]] */
    public const METRICS = [
        'AV' => ['Attack Vector',       ['N' => 'Network', 'A' => 'Adjacent', 'L' => 'Local', 'P' => 'Physical']],
        'AC' => ['Attack Complexity',   ['L' => 'Low', 'H' => 'High']],
        'PR' => ['Privileges Required', ['N' => 'None', 'L' => 'Low', 'H' => 'High']],
        'UI' => ['User Interaction',    ['N' => 'None', 'R' => 'Required']],
        'S'  => ['Scope',               ['U' => 'Unchanged', 'C' => 'Changed']],
        'C'  => ['Confidentiality',     ['H' => 'High', 'L' => 'Low', 'N' => 'None']],
        'I'  => ['Integrity',           ['H' => 'High', 'L' => 'Low', 'N' => 'None']],
        'A'  => ['Availability',        ['H' => 'High', 'L' => 'Low', 'N' => 'None']],
    ];

    private const W = [
        'AV'  => ['N' => 0.85, 'A' => 0.62, 'L' => 0.55, 'P' => 0.2],
        'AC'  => ['L' => 0.77, 'H' => 0.44],
        'PRU' => ['N' => 0.85, 'L' => 0.62, 'H' => 0.27],
        'PRC' => ['N' => 0.85, 'L' => 0.68, 'H' => 0.5],
        'UI'  => ['N' => 0.85, 'R' => 0.62],
        'CIA' => ['H' => 0.56, 'L' => 0.22, 'N' => 0.0],
    ];

    /** Validasi & normalisasi input metrik. Mengembalikan null jika tidak valid. */
    public static function normalize(array $input): ?array
    {
        $out = [];
        foreach (self::METRICS as $key => [, $options]) {
            $val = $input[$key] ?? null;
            if (!is_string($val) || !array_key_exists($val, $options)) {
                return null;
            }
            $out[$key] = $val;
        }
        return $out;
    }

    public static function score(array $m): float
    {
        $changed = $m['S'] === 'C';
        $iss = 1 - ((1 - self::W['CIA'][$m['C']]) * (1 - self::W['CIA'][$m['I']]) * (1 - self::W['CIA'][$m['A']]));

        $impact = $changed
            ? 7.52 * ($iss - 0.029) - 3.25 * (($iss - 0.02) ** 15)
            : 6.42 * $iss;

        $pr = $changed ? self::W['PRC'][$m['PR']] : self::W['PRU'][$m['PR']];
        $exploitability = 8.22 * self::W['AV'][$m['AV']] * self::W['AC'][$m['AC']] * $pr * self::W['UI'][$m['UI']];

        if ($impact <= 0) {
            return 0.0;
        }
        $base = $changed
            ? min(1.08 * ($impact + $exploitability), 10)
            : min($impact + $exploitability, 10);

        return self::roundUp($base);
    }

    /** Fungsi Roundup sesuai Appendix A spesifikasi CVSS v3.1. */
    public static function roundUp(float $value): float
    {
        $int = (int) round($value * 100000);
        if ($int % 10000 === 0) {
            return $int / 100000.0;
        }
        return (floor($int / 10000) + 1) / 10.0;
    }

    public static function severity(float $score): string
    {
        return match (true) {
            $score >= 9.0 => 'Critical',
            $score >= 7.0 => 'High',
            $score >= 4.0 => 'Medium',
            $score > 0.0  => 'Low',
            default       => 'None',
        };
    }

    public static function vector(array $m): string
    {
        $parts = ['CVSS:3.1'];
        foreach (array_keys(self::METRICS) as $k) {
            $parts[] = $k . ':' . $m[$k];
        }
        return implode('/', $parts);
    }

    /** Parse string vektor menjadi array metrik (untuk tampilan detail). */
    public static function parseVector(string $vector): array
    {
        $out = [];
        foreach (explode('/', $vector) as $part) {
            [$k, $v] = array_pad(explode(':', $part, 2), 2, null);
            if (isset(self::METRICS[$k])) {
                $out[$k] = $v;
            }
        }
        return $out;
    }
}

