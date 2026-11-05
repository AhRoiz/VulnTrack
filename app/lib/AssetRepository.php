<?php
/**
 * AssetRepository - Data aset (aplikasi / server / environment yang diuji).
 */

declare(strict_types=1);

final class AssetRepository
{
    public const ENVIRONMENTS = ['production', 'staging', 'development'];

    public function __construct(private JsonStore $store)
    {
    }

    public function all(): array
    {
        return $this->store->collection('assets');
    }

    public function find(int $id): ?array
    {
        foreach ($this->all() as $a) {
            if ((int) $a['id'] === $id) {
                return $a;
            }
        }
        return null;
    }

    /** Peta id => asset untuk lookup cepat di view. */
    public function map(): array
    {
        return array_column($this->all(), null, 'id');
    }

    /** @throws ValidationException */
    public function create(array $input): int
    {
        $name = trim((string) ($input['asset_name'] ?? ''));
        $env  = (string) ($input['environment'] ?? '');
        $desc = trim((string) ($input['description'] ?? ''));

        $errors = [];
        if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            $errors['asset_name'] = 'Nama aset wajib diisi (3–100 karakter).';
        }
        if (!in_array($env, self::ENVIRONMENTS, true)) {
            $errors['environment'] = 'Environment tidak valid.';
        }
        if (mb_strlen($desc) > 500) {
            $errors['description'] = 'Deskripsi maksimal 500 karakter.';
        }
        if ($errors) {
            throw new ValidationException($errors);
        }

        return $this->store->transaction(function (array &$data) use ($name, $env, $desc) {
            $id = JsonStore::nextId($data, 'assets');
            $data['assets'][] = [
                'id'          => $id,
                'asset_name'  => $name,
                'environment' => $env,
                'description' => $desc,
            ];
            return $id;
        });
    }
}

