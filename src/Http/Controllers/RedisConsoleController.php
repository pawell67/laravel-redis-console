<?php

declare(strict_types=1);

namespace Pawell67\RedisConsole\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;
use Exception;

class RedisConsoleController extends Controller
{
    public function index()
    {
        return view('redis-console::index', [
            'connections' => array_keys(config('database.redis', [])),
            'maxDb' => config('redis-console.max_db', 15),
        ]);
    }

    public function execute(Request $request): JsonResponse
    {
        $raw = trim((string) $request->input('command', ''));
        $connection = $this->resolveConnection($request->input('connection'));
        $db = $request->input('db');

        if (empty($raw)) {
            return response()->json(['error' => 'No command provided.'], 400);
        }

        $parts = $this->parseCommand($raw);
        $cmd = strtoupper(array_shift($parts));

        if (in_array($cmd, config('redis-console.blocked_commands', []))) {
            return response()->json(['error' => "Command '{$cmd}' is blocked."], 403);
        }

        if (config('redis-console.read_only', false)) {
            $allowed = array_map('strtoupper', config('redis-console.read_only_commands', []));
            // Check single-word command first, then compound (e.g. "CONFIG GET")
            $compoundCmd = isset($parts[0]) ? $cmd . ' ' . strtoupper($parts[0]) : null;
            if (! in_array($cmd, $allowed) && ($compoundCmd === null || ! in_array($compoundCmd, $allowed))) {
                return response()->json(['error' => "Command '{$cmd}' is not allowed in read-only mode."], 403);
            }
        }

        $isDangerous = in_array($cmd, config('redis-console.dangerous_commands', []));

        try {
            $redis = $this->getRedis($connection, $db);
            $result = $redis->client()->rawCommand($cmd, ...$parts);

            return response()->json([
                'command' => $raw,
                'result' => $this->formatResult($result),
                'type' => gettype($result),
                'dangerous' => $isDangerous,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'command' => $raw,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function info(Request $request): JsonResponse
    {
        $connection = $this->resolveConnection($request->input('connection'));
        $db = $request->input('db');

        try {
            $redis = $this->getRedis($connection, $db);
            $info = $redis->client()->rawCommand('INFO');

            return response()->json([
                'info' => $info,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function keys(Request $request): JsonResponse
    {
        $connection = $this->resolveConnection($request->input('connection'));
        $db = $request->input('db');
        $pattern = $request->input('pattern', '*');
        $cursor = $request->input('cursor', '0');
        $count = min((int) $request->input('count', 100), 500);

        try {
            $redis = $this->getRedis($connection, $db);
            $result = $redis->client()->rawCommand('SCAN', $cursor, 'MATCH', $pattern, 'COUNT', $count);

            $nextCursor = $result[0] ?? '0';
            $keys = $result[1] ?? [];

            // Get types for each key
            $keysWithTypes = [];
            foreach ($keys as $key) {
                $type = $this->resolveType($redis->client(), $key);
                $ttl = $redis->client()->rawCommand('TTL', $key);
                $keysWithTypes[] = [
                    'key' => $key,
                    'type' => $type,
                    'ttl' => $ttl,
                ];
            }

            return response()->json([
                'cursor' => (string) $nextCursor,
                'keys' => $keysWithTypes,
                'done' => $nextCursor === '0' || $nextCursor === 0,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function count(Request $request): JsonResponse
    {
        $connection = $this->resolveConnection($request->input('connection'));
        $db = $request->input('db');
        $pattern = $request->input('pattern', '*');

        try {
            $redis = $this->getRedis($connection, $db);
            $client = $redis->client();
            $cursor = '0';
            $total = 0;
            $maxIterations = 1000; // Safety limit

            do {
                $result = $client->rawCommand('SCAN', $cursor, 'MATCH', $pattern, 'COUNT', 500);
                $cursor = $result[0] ?? '0';
                $keys = $result[1] ?? [];
                $total += count($keys);
                $maxIterations--;
            } while (($cursor !== '0' && $cursor !== 0) && $maxIterations > 0);

            return response()->json([
                'pattern' => $pattern,
                'count' => $total,
                'complete' => $maxIterations > 0,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function inspect(Request $request): JsonResponse
    {
        $key = $request->input('key', '');
        $connection = $this->resolveConnection($request->input('connection'));
        $db = $request->input('db');

        if (empty($key)) {
            return response()->json(['error' => 'No key provided.'], 400);
        }

        try {
            $redis = $this->getRedis($connection, $db);

            $client = $redis->client();

            $type = $this->resolveType($client, $key);
            $ttl = $client->rawCommand('TTL', $key);
            $encoding = $client->rawCommand('OBJECT', 'ENCODING', $key);

            // Get value based on type
            $value = match ($type) {
                'string' => $client->rawCommand('GET', $key),
                'list' => $client->rawCommand('LRANGE', $key, '0', '99'),
                'set' => $client->rawCommand('SMEMBERS', $key),
                'zset' => $client->rawCommand('ZRANGE', $key, '0', '99', 'WITHSCORES'),
                'hash' => $client->rawCommand('HGETALL', $key),
                'stream' => $client->rawCommand('XRANGE', $key, '-', '+', 'COUNT', '20'),
                default => '(unknown type)',
            };

            // Calculate expiry timestamp
            $expiresAt = null;
            if ($ttl > 0) {
                $expiresAt = now()->addSeconds($ttl)->toDateTimeString();
            }

            return response()->json([
                'key' => $key,
                'type' => $type,
                'value' => $this->formatResult($value),
                'ttl' => $ttl,
                'expires_at' => $expiresAt,
                'encoding' => $encoding,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a Redis connection, optionally selecting a specific DB index.
     */
    protected function getRedis(string $connection, ?string $db = null)
    {
        $redis = Redis::connection($connection);

        if ($db !== null && $db !== '') {
            $dbIndex = max(0, min((int) $db, config('redis-console.max_db', 15)));
            $redis->client()->rawCommand('SELECT', $dbIndex);
        }

        return $redis;
    }

    /**
     * Validate and resolve the connection name against configured connections.
     */
    protected function resolveConnection(?string $connection): string
    {
        $default = config('redis-console.connection', 'default');
        $connection = $connection ?: $default;

        // Validate against configured Redis connections
        $configured = array_keys(config('database.redis', []));
        $allowed = array_filter($configured, fn ($c) => ! in_array($c, ['client', 'options']));

        if (! in_array($connection, $allowed)) {
            return $default;
        }

        return $connection;
    }

    /**
     * Resolve the Redis type for a key using phpredis native type() method.
     * rawCommand('TYPE') returns a status reply (boolean), not a usable string.
     */
    protected function resolveType(object $client, string $key): string
    {
        $typeInt = $client->type($key);

        return match ($typeInt) {
            1 => 'string',  // Redis::REDIS_STRING
            2 => 'set',     // Redis::REDIS_SET
            3 => 'list',    // Redis::REDIS_LIST
            4 => 'zset',    // Redis::REDIS_ZSET
            5 => 'hash',    // Redis::REDIS_HASH
            6 => 'stream',  // Redis::REDIS_STREAM
            default => 'none',
        };
    }

    protected function parseCommand(string $raw): array
    {
        // Handle quoted strings properly
        $parts = [];
        $current = '';
        $inQuote = false;
        $quoteChar = '';

        for ($i = 0; $i < strlen($raw); $i++) {
            $char = $raw[$i];

            if ($inQuote) {
                if ($char === $quoteChar) {
                    $inQuote = false;
                } else {
                    $current .= $char;
                }
            } elseif ($char === '"' || $char === "'") {
                $inQuote = true;
                $quoteChar = $char;
            } elseif ($char === ' ') {
                if ($current !== '') {
                    $parts[] = $current;
                    $current = '';
                }
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    protected function formatResult(mixed $result): mixed
    {
        if (is_array($result)) {
            return array_map(fn ($item) => $this->formatResult($item), $result);
        }

        if (is_bool($result)) {
            return $result ? '(integer) 1' : '(integer) 0';
        }

        if (null === $result) {
            return '(nil)';
        }

        return $result;
    }
}
