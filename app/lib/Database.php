<?php

declare(strict_types=1);

final class Database
{
    private static ?Database $instance = null;

    private PDO $pdo;

    private function __construct()
    {
        $host    = self::setting('DB_HOST', '127.0.0.1');
        $port    = self::setting('DB_PORT', '3306');
        $name    = self::setting('DB_NAME', 'vulntrack');
        $user    = self::setting('DB_USER', 'root');
        $pass    = self::setting('DB_PASS', '');
        $charset = self::setting('DB_CHARSET', 'utf8mb4');
        $custom  = self::setting('DB_DSN', '');

        $dsn = $custom !== ''
            ? $custom
            : sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Native prepares keep parameters out of the SQL text entirely.
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        try {
            $this->pdo = $this->connect($dsn, $host, $port, $charset, $user, $pass, $options);
        } catch (PDOException $e) {
            // Log the driver message server-side; never echo DSN or credentials to the browser.
            error_log('[VulnTrack] Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Koneksi ke database gagal. Pastikan layanan MySQL/MariaDB aktif dan skema database telah diimpor.');
        }
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $param = is_int($key) ? $key + 1 : (str_starts_with($key, ':') ? $key : ':' . $key);
            $stmt->bindValue($param, $value, self::paramType($value));
        }
        $stmt->execute();
        return $stmt;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function fetchValue(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public function insert(string $sql, array $params = []): int
    {
        $this->run($sql, $params);
        return (int) $this->pdo->lastInsertId();
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /**
     * Runs the callback inside a transaction and rolls back on any throwable.
     * Nested calls join the outer transaction instead of opening a new one.
     */
    public function transaction(callable $callback): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $callback($this);
        }

        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function connect(
        string $dsn,
        string $host,
        string $port,
        string $charset,
        string $user,
        string $pass,
        array $options
    ): PDO {
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // SQLSTATE[HY000] [1049]: Unknown database -> bootstrap from database/schema.sql.
            if (str_starts_with($dsn, 'mysql:') && (int) ($e->errorInfo[1] ?? 0) === 1049) {
                $this->provisionMySql($host, $port, $charset, $user, $pass, $options);
                $pdo = new PDO($dsn, $user, $pass, $options);
            } else {
                throw $e;
            }
        }

        if (str_starts_with($dsn, 'mysql:')) {
            $pdo->exec("SET time_zone = '" . (new DateTimeImmutable())->format('P') . "'");
            $hasUsers = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
            if ($hasUsers === false) {
                $this->provisionMySql($host, $port, $charset, $user, $pass, $options);
            }
        }

        return $pdo;
    }

    private function provisionMySql(
        string $host,
        string $port,
        string $charset,
        string $user,
        string $pass,
        array $options
    ): void {
        $schemaFile = defined('BASE_PATH') ? BASE_PATH . '/database/schema.sql' : dirname(__DIR__, 2) . '/database/schema.sql';
        if (!is_file($schemaFile)) {
            return;
        }

        $serverDsn = sprintf('mysql:host=%s;port=%s;charset=%s', $host, $port, $charset);
        $pdo = new PDO($serverDsn, $user, $pass, $options);
        $pdo->exec("SET time_zone = '" . (new DateTimeImmutable())->format('P') . "'");

        $sql = (string) file_get_contents($schemaFile);
        $clean = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $clean) ?: [];

        foreach ($statements as $statement) {
            $trimmed = trim($statement);
            if ($trimmed !== '') {
                $pdo->exec($trimmed);
            }
        }
    }

    private static function paramType(mixed $value): int
    {
        return match (true) {
            is_int($value)  => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            $value === null => PDO::PARAM_NULL,
            default         => PDO::PARAM_STR,
        };
    }

    private static function setting(string $name, string $default): string
    {
        if (defined($name)) {
            return (string) constant($name);
        }
        $env = getenv($name);
        return $env !== false ? $env : $default;
    }

    private function __clone()
    {
    }
}
