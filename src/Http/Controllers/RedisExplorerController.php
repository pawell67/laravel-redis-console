<?php

declare(strict_types=1);

namespace Pawell67\RedisExplorer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;
use Exception;

class RedisExplorerController extends Controller
{
    public function index()
    {
        return view('redis-explorer::index', [
            'connections' => array_keys(config('database.redis', [])),
            'maxDb' => config('redis-explorer.max_db', 15),
        ]);
    }

    public function execute(Request $request): JsonResponse
    {
        $raw = trim((string) $request->input('command', ''));
        $connection = $request->input('connection', config('redis-explorer.connection', 'default'));
        $db = $request->input('db');

        if (empty($raw)) {
            return response()->json(['error' => 'No command provided.'], 400);
        }

        $parts = $this->parseCommand($raw);
        $cmd = strtoupper(array_shift($parts));

        if (in_array($cmd, config('redis-explorer.blocked_commands', []))) {
            return response()->json(['error' => "Command '{$cmd}' is blocked."], 403);
        }

        $isDangerous = in_array($cmd, config('redis-explorer.dangerous_commands', []));

        try {
            $redis = $this->getRedis($connection, $db);
            $result = $redis->command($cmd, $parts);

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
        $connection = $request->input('connection', config('redis-explorer.connection', 'default'));
        $db = $request->input('db');

        try {
            $redis = $this->getRedis($connection, $db);
            $info = $redis->command('INFO');

            return response()->json([
                'info' => $info,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function keys(Request $request): JsonResponse
    {
        $connection = $request->input('connection', config('redis-explorer.connection', 'default'));
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
                $type = $redis->client()->rawCommand('TYPE', $key);
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

    /**
     * Get a Redis connection, optionally selecting a specific DB index.
     */
    protected function getRedis(string $connection, ?string $db = null)
    {
        $redis = Redis::connection($connection);

        if ($db !== null && $db !== '') {
            $dbIndex = max(0, min((int) $db, config('redis-explorer.max_db', 15)));
            $redis->command('SELECT', [$dbIndex]);
        }

        return $redis;
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
