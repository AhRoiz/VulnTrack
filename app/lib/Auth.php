<?php

declare(strict_types=1);

final class Auth
{
    private static ?array $users = null;

    public static function users(): array
    {
        return self::$users ??= require APP_PATH . '/users.php';
    }

    public static function findUser(?int $id): ?array
    {
        return $id !== null ? (self::users()[$id] ?? null) : null;
    }

    public static function usersByRole(string $role): array
    {
        return array_values(array_filter(self::users(), fn ($u) => $u['role'] === $role));
    }

    public static function attempt(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        $match = null;
        foreach (self::users() as $u) {
            if (hash_equals($u['email'], $email)) {
                $match = $u;
                break;
            }
        }

        // Tetap jalankan password_verify walau user tidak ditemukan (mitigasi timing attack)
        $hash = $match['password'] ?? '$2y$10$emcKewURNuEhOxEA3JlOPOIiu5qMeOc9V41.YpiWp5MJizow75WHO';
        $valid = password_verify($password, $hash);
        if (!$valid || $match === null) {
            return false;
        }

        session_regenerate_id(true); // cegah session fixation
        $_SESSION['user'] = [
            'id'    => $match['id'],
            'name'  => $match['name'],
            'email' => $match['email'],
            'role'  => $match['role'],
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
