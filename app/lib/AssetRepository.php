<?php

declare(strict_types=1);

final class AssetRepository
{
    public const ENVIRONMENTS = ['production', 'staging', 'development'];

    public function __construct(private Database $db)
    {
    }

    public function all(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT id, asset_name, environment, description, created_at FROM assets ORDER BY id ASC'
        );

        return array_map([self::class, 'normalize'], $rows);
    }

    public function find(int $id): ?array
    {
        $row = $this->db->fetchOne(
            'SELECT id, asset_name, environment, description, created_at FROM assets WHERE id = :id LIMIT 1',
            ['id' => $id]
        );

        return $row !== null ? self::normalize($row) : null;
    }

    public function map(): array
    {
        return array_column($this->all(), null, 'id');
    }

    /** @throws ValidationException */
    public function create(array $input): int
    {
        [$name, $env, $desc] = $this->validate($input);

        return $this->db->insert(
            'INSERT INTO assets (asset_name, environment, description, created_at)
             VALUES (:asset_name, :environment, :description, :created_at)',
            [
                'asset_name'  => $name,
                'environment' => $env,
                'description' => $desc,
                'created_at'  => date('Y-m-d H:i:s'),
            ]
        );
    }

    /** @throws ValidationException */
    public function update(int $id, array $input): bool
    {
        [$name, $env, $desc] = $this->validate($input);

        return $this->db->execute(
            'UPDATE assets
             SET asset_name = :asset_name, environment = :environment, description = :description
             WHERE id = :id',
            [
                'id'          => $id,
                'asset_name'  => $name,
                'environment' => $env,
                'description' => $desc,
            ]
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM assets WHERE id = :id', ['id' => $id]) > 0;
    }

    /**
     * @return array{0:string,1:string,2:string}
     * @throws ValidationException
     */
    private function validate(array $input): array
    {
        $name = trim((string) ($input['asset_name'] ?? ''));
        $env  = (string) ($input['environment'] ?? '');
        $desc = trim((string) ($input['description'] ?? ''));

        $errors = [];
        if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            $errors['asset_name'] = 'Nama aset wajib diisi (3 sampai 100 karakter).';
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

        return [$name, $env, $desc];
    }

    private static function normalize(array $row): array
    {
        return [
            'id'          => (int) $row['id'],
            'asset_name'  => (string) $row['asset_name'],
            'environment' => (string) $row['environment'],
            'description' => (string) $row['description'],
            'created_at'  => (string) ($row['created_at'] ?? ''),
        ];
    }
}
