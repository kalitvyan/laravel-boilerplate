<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Outbox;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use LogicException;
use PDO;
use Pdo\Pgsql;

/**
 * Отдельное соединение под LISTEN: рабочее соединение Laravel может переподключиться
 * и потерять подписку, а уведомления не доставляются внутри открытой транзакции.
 */
final class PostgresNotificationListener
{
    private ?Pgsql $pdo = null;

    public function __construct(private readonly ConnectionResolverInterface $db) {}

    public function listen(string $channel): void
    {
        if (preg_match('/^[a-z_][a-z0-9_]*$/', $channel) !== 1) {
            throw new LogicException(sprintf('Invalid channel name "%s"', $channel));
        }

        $this->pdo = $this->connect();
        $this->pdo->exec('LISTEN '.$channel);
    }

    /**
     * Ждёт уведомление не дольше $timeoutMs. Пачку уведомлений схлопывает в одно пробуждение.
     */
    public function wait(int $timeoutMs): bool
    {
        if (! $this->pdo instanceof Pgsql) {
            usleep($timeoutMs * 1000);

            return false;
        }

        $received = $this->pdo->getNotify(PDO::FETCH_ASSOC, $timeoutMs) !== false;

        while ($received && $this->pdo->getNotify(PDO::FETCH_ASSOC, 0) !== false) {
            // дренируем очередь уведомлений
        }

        return $received;
    }

    private function connect(): Pgsql
    {
        $connection = $this->db->connection();

        if (! $connection instanceof Connection) {
            throw new LogicException('Unsupported database connection');
        }

        $option = static function (string $key) use ($connection): string {
            $value = $connection->getConfig($key);

            return is_scalar($value) ? (string) $value : '';
        };

        return new Pgsql(
            sprintf('pgsql:host=%s;port=%s;dbname=%s', $option('host'), $option('port'), $option('database')),
            $option('username'),
            $option('password'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    }
}
