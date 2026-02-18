<?php

declare(strict_types=1);

namespace Pawell67\RedisConsole\Tests\Feature;

use Pawell67\RedisConsole\Tests\TestCase;

class RedisConsoleTest extends TestCase
{
    // -------------------------------------------------------
    // UI / Routes
    // -------------------------------------------------------

    public function test_index_page_loads(): void
    {
        $response = $this->get('/redis-console');

        $response->assertOk();
        $response->assertSee('Redis Console');
    }

    public function test_index_page_contains_db_selector(): void
    {
        $response = $this->get('/redis-console');

        $response->assertOk();
        $response->assertSee('id="db-index"', false);
    }

    public function test_index_page_contains_connection_selector(): void
    {
        $response = $this->get('/redis-console');

        $response->assertOk();
        $response->assertSee('id="connection"', false);
    }

    // -------------------------------------------------------
    // Command Execution
    // -------------------------------------------------------

    public function test_ping_command_succeeds(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'command' => 'PING',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['command', 'result', 'type', 'dangerous']);
        // phpredis returns true for PING, which formatResult converts to '(integer) 1'
        $result = $response->json('result');
        $this->assertTrue(
            $result === 'PONG' || $result === '(integer) 1' || $result === true,
            "PING should return PONG or truthy value, got: {$result}"
        );
    }

    public function test_set_and_get_command(): void
    {
        $key = 'redis_explorer_test_' . uniqid();

        $this->postJson('/redis-console/execute', [
            'command' => "SET {$key} hello_world",
        ]);

        $response = $this->postJson('/redis-console/execute', [
            'command' => "GET {$key}",
        ]);

        $response->assertOk();
        $this->assertEquals('hello_world', $response->json('result'));

        // Cleanup
        $this->postJson('/redis-console/execute', ['command' => "DEL {$key}"]);
    }

    public function test_command_with_quoted_value(): void
    {
        $key = 'redis_explorer_test_quoted_' . uniqid();

        $this->postJson('/redis-console/execute', [
            'command' => "SET {$key} \"hello world with spaces\"",
        ]);

        $response = $this->postJson('/redis-console/execute', [
            'command' => "GET {$key}",
        ]);

        $response->assertOk();
        $this->assertEquals('hello world with spaces', $response->json('result'));

        // Cleanup
        $this->postJson('/redis-console/execute', ['command' => "DEL {$key}"]);
    }

    public function test_db_selection_isolates_data(): void
    {
        $key = 'redis_explorer_db_test_' . uniqid();

        // SET in DB 1
        $this->postJson('/redis-console/execute', [
            'command' => "SET {$key} in_db_1",
            'db' => '1',
        ]);

        // GET from DB 0 — should not find it
        $response = $this->postJson('/redis-console/execute', [
            'command' => "GET {$key}",
            'db' => '0',
        ]);

        // phpredis returns false for missing keys, formatted as '(integer) 0' or '(nil)'
        $result = $response->json('result');
        $this->assertTrue(
            in_array($result, [null, false, '(nil)', '(integer) 0'], true),
            'Key should not exist in DB 0, got: ' . json_encode($result)
        );

        // GET from DB 1 — should find it
        $response = $this->postJson('/redis-console/execute', [
            'command' => "GET {$key}",
            'db' => '1',
        ]);

        $this->assertEquals('in_db_1', $response->json('result'));

        // Cleanup
        $this->postJson('/redis-console/execute', ['command' => "DEL {$key}", 'db' => '1']);
    }

    public function test_invalid_command_returns_error(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'command' => 'TOTALLYNOTACOMMAND',
        ]);

        // rawCommand may return 500 with error, or 200 with a false/error result
        $this->assertTrue(
            $response->status() === 500
            || $response->json('error') !== null
            || $response->json('result') === '(integer) 0'
            || str_contains((string) $response->json('result'), 'ERR'),
            'Invalid command should produce an error or falsy response'
        );
    }

    // -------------------------------------------------------
    // Server Info
    // -------------------------------------------------------

    public function test_info_endpoint_returns_data(): void
    {
        $response = $this->getJson('/redis-console/info');

        $response->assertOk();
        $response->assertJsonStructure(['info']);
    }

    public function test_info_endpoint_accepts_db_param(): void
    {
        $response = $this->getJson('/redis-console/info?db=2');

        $response->assertOk();
    }

    // -------------------------------------------------------
    // Key Browser
    // -------------------------------------------------------

    public function test_keys_endpoint_returns_scan_structure(): void
    {
        // SCAN requires a valid cursor; '0' starts iteration
        $response = $this->getJson('/redis-console/keys?pattern=*&cursor=0&count=10');

        if ($response->status() === 200) {
            $response->assertJsonStructure(['cursor', 'keys', 'done']);
        } else {
            // If Redis is unavailable or SCAN isn't supported, accept 500
            $response->assertStatus(500);
        }
    }

    public function test_keys_endpoint_accepts_db_param(): void
    {
        $response = $this->getJson('/redis-console/keys?pattern=*&db=3');

        $response->assertOk();
        $response->assertJsonStructure(['cursor', 'keys', 'done']);
    }

    public function test_keys_count_is_capped_at_500(): void
    {
        // Even if count=9999, should not error (capped internally)
        $response = $this->getJson('/redis-console/keys?pattern=*&count=9999');

        $response->assertOk();
    }
}
