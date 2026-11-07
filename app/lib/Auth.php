<?php

declare(strict_types=1);

final class Auth
{
    private static ?array $users = null;

    public static function users(): array
    {
        if (self::$users !== null) {
            return self::$users;
        }

        $rows = db()->fetchAll('SELECT id, name, email, role FROM users WHERE is_active = 1 ORDER BY id ASC');
        $indexed = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $indexed[$id] = [
                'id'    => $id,
                'name'  => (string) $row['name'],
                'email' => (string) $row['email'],
                'role'  => (string) $row['role'],
            ];
        }

        return self::$users = $indexed;
    }

    public static function findUser(?int $id): ?array
    {
        if ($id === null || $id <= 0) {
            return null;
        }

        $cached = self::users();
        if (isset($cached[$id])) {
            return $cached[$id];
        }

        $row = db()->fetchOne('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($row === null) {
            return null;
        }

        return self::$users[$id] = [
            'id'    => (int) $row['id'],
            'name'  => (string) $row['name'],
            'email' => (string) $row['email'],
            'role'  => (string) $row['role'],
        ];
    }

    public static function usersByRole(string $role): array
    {
        $rows = db()->fetchAll(
            'SELECT id, name, email, role FROM users WHERE role = :role AND is_active = 1 ORDER BY id ASC',
            ['role' => $role]
        );

        return array_map(static fn (array $row): array => [
            'id'    => (int) $row['id'],
            'name'  => (string) $row['name'],
            'email' => (string) $row['email'],
            'role'  => (string) $row['role'],
        ], $rows);
    }

    public static function attempt(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        $match = db()->fetchOne(
            'SELECT id, name, email, password_hash, role FROM users WHERE email = :email AND is_active = 1 LIMIT 1',
            ['email' => $email]
        );

        // Run password_verify even when the email does not exist to prevent timing enumeration.
        $hash = (string) ($match['password_hash'] ?? '$2y$10$emcKewURNuEhOxEA3JlOPOIiu5qMeOc9V41.YpiWp5MJizow75WHO');
        $valid = password_verify($password, $hash);
        if (!$valid || $match === null) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'    => (int) $match['id'],
            'name'  => (string) $match['name'],
            'email' => (string) $match['email'],
            'role'  => (string) $match['role'],
        ];
        $_SESSION['login_at'] = time();
        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string ...$roles): bool
    {
        return self::check() && in_array(self::user()['role'], $roles, true);
    }

    public static function requireLogin(): array
    {
        if (!self::check()) {
            flash('warning', 'Silakan login terlebih dahulu.');
            redirect('login.php');
        }
        return self::user();
    }

    public static function requireRole(string ...$roles): array
    {
        $user = self::requireLogin();
        if (!in_array($user['role'], $roles, true)) {
            abort(403, 'Anda tidak memiliki hak akses ke halaman ini.');
        }
        return $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'admin'     => 'Admin',
            'analyst'   => 'Security Analyst',
            'developer' => 'Developer',
            default     => ucfirst($role),
        };
    }
}
