<?php
/**
 * JsonStore - Penyimpanan berbasis file JSON (pengganti MySQL pada Checkpoint 1).
 *
 * Semua operasi tulis dilakukan di dalam transaksi dengan file lock (flock)
 * sehingga aman terhadap race-condition antar request.
 */

declare(strict_types=1);

final class JsonStore
{
    private const COLLECTIONS = ['assets', 'vulnerabilities', 'remediation_notes', 'audit_logs', 'forum_threads', 'forum_replies'];

    public function __construct(private string $file)
    {
        if (!is_file($this->file)) {
            $this->seed();
        }
    }

    /** Membaca seluruh data dengan shared lock agar tidak membaca file yang sedang ditulis. */
    public function read(): array
    {
        $fp = fopen($this->file, 'rb');
        if ($fp === false) {
            throw new RuntimeException('Tidak dapat membuka file data.');
        }
        try {
            flock($fp, LOCK_SH);
            $raw = stream_get_contents($fp);
            flock($fp, LOCK_UN);
        } finally {
            fclose($fp);
        }
        return $this->decode((string) $raw);
    }

    public function collection(string $name): array
    {
        return $this->read()[$name] ?? [];
    }

    /**
     * Menjalankan perubahan data secara atomik dengan exclusive lock (flock LOCK_EX).
     * Callback menerima data by-reference dan boleh mengembalikan nilai apa pun.
     */
    public function transaction(callable $callback): mixed
    {
        $fp = fopen($this->file, 'c+b');
        if ($fp === false) {
            throw new RuntimeException('Tidak dapat membuka file data.');
        }
        try {
            flock($fp, LOCK_EX);
            $data = $this->decode((string) stream_get_contents($fp));
            $result = $callback($data);

            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $json);
            fflush($fp);
            flock($fp, LOCK_UN);
        } finally {
            fclose($fp);
        }
        return $result;
    }

    public static function nextId(array &$data, string $collection): int
    {
        $data['counters'][$collection] = ($data['counters'][$collection] ?? 0) + 1;
        return $data['counters'][$collection];
    }

    private function decode(string $raw): array
    {
        $data = $raw === '' ? [] : json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('File data rusak (JSON tidak valid).');
        }
        foreach (self::COLLECTIONS as $c) {
            $data[$c] ??= [];
        }
        $data['counters'] ??= [];
        return $data;
    }

    private function seed(): void
    {
        if (!is_dir(dirname($this->file))) {
            mkdir(dirname($this->file), 0775, true);
        }
        $seed = require APP_PATH . '/seed.php';
        file_put_contents(
            $this->file,
            json_encode($seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }
}

