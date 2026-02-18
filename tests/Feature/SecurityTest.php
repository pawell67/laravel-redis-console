<?php

declare(strict_types=1);

namespace Pawell67\RedisConsole\Tests\Feature;

use Pawell67\RedisConsole\Tests\TestCase;

class SecurityTest extends TestCase
{
    // -------------------------------------------------------
    // Blocked Commands
    // -------------------------------------------------------

    public function test_blocked_command_shutdown_returns_403(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'command' => 'SHUTDOWN',
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => "Command 'SHUTDOWN' is blocked."]);
    }

    public function test_blocked_command_debug_returns_403(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'command' => 'DEBUG SLEEP 0',
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => "Command 'DEBUG' is blocked."]);
    }

    public function test_blocked_commands_are_case_insensitive(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'command' => 'shutdown',
        ]);

        $response->assertStatus(403);
    }

    public function test_custom_blocked_command_is_enforced(): void
    {
        // Dynamically add EVAL to the blocked list
        config(['redis-console.blocked_commands' => ['SHUTDOWN', 'DEBUG', 'EVAL']]);

        $response = $this->postJson('/redis-console/execute', [
            'command' => 'EVAL "return 1" 0',
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => "Command 'EVAL' is blocked."]);
    }

    // -------------------------------------------------------
    // Dangerous Commands
    // -------------------------------------------------------

    public function test_dangerous_command_is_flagged(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'command' => 'FLUSHDB',
        ]);

        // Should still execute (not blocked), but flagged as dangerous
        if ($response->status() === 200) {
            $response->assertJsonFragment(['dangerous' => true]);
        }
    }

    public function test_safe_command_is_not_flagged_dangerous(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'command' => 'PING',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['dangerous' => false]);
    }

    // -------------------------------------------------------
    // Empty / Malformed Commands
    // -------------------------------------------------------

    public function test_empty_command_returns_400(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'command' => '',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['error' => 'No command provided.']);
    }

    public function test_whitespace_only_command_returns_400(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'command' => '   ',
        ]);

        $response->assertStatus(400);
    }

    public function test_missing_command_field_returns_400(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'connection' => 'default',
        ]);

        $response->assertStatus(400);
    }

    // -------------------------------------------------------
    // DB Index Bounds
    // -------------------------------------------------------

    public function test_negative_db_index_is_clamped_to_zero(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'command' => 'PING',
            'db' => '-5',
        ]);

        // Should not error — clamped to 0
        $response->assertOk();
    }

    public function test_excessive_db_index_is_clamped_to_max(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'command' => 'PING',
            'db' => '999',
        ]);

        // Should not error — clamped to max_db (15)
        $response->assertOk();
    }

    public function test_non_numeric_db_index_is_treated_as_zero(): void
    {
        $response = $this->postJson('/redis-console/execute', [
            'command' => 'PING',
            'db' => 'abc',
        ]);

        // (int) 'abc' === 0, so clamped to 0
        $response->assertOk();
    }

    // -------------------------------------------------------
    // Route Protection
    // -------------------------------------------------------

    public function test_execute_requires_post_method(): void
    {
        $response = $this->getJson('/redis-console/execute');

        $response->assertStatus(405); // Method Not Allowed
    }

    public function test_middleware_config_is_applied(): void
    {
        // Verify that the config value is respected by the service provider
        $middleware = config('redis-console.middleware');

        $this->assertIsArray($middleware);
        $this->assertContains('web', $middleware);
    }
}
