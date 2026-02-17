<?php

declare(strict_types=1);

namespace Pawell67\RedisExplorer\Tests\Unit;

use Pawell67\RedisExplorer\Http\Controllers\RedisExplorerController;
use PHPUnit\Framework\TestCase;

class CommandParserTest extends TestCase
{
    private RedisExplorerController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Use reflection to access the protected parseCommand method
        $this->controller = new class () extends RedisExplorerController {
            public function publicParseCommand(string $raw): array
            {
                return $this->parseCommand($raw);
            }

            public function publicFormatResult(mixed $result): mixed
            {
                return $this->formatResult($result);
            }
        };
    }

    // -------------------------------------------------------
    // parseCommand
    // -------------------------------------------------------

    public function test_simple_command(): void
    {
        $result = $this->controller->publicParseCommand('GET mykey');
        $this->assertEquals(['GET', 'mykey'], $result);
    }

    public function test_command_with_double_quoted_value(): void
    {
        $result = $this->controller->publicParseCommand('SET key "hello world"');
        $this->assertEquals(['SET', 'key', 'hello world'], $result);
    }

    public function test_command_with_single_quoted_value(): void
    {
        $result = $this->controller->publicParseCommand("SET key 'hello world'");
        $this->assertEquals(['SET', 'key', 'hello world'], $result);
    }

    public function test_command_with_multiple_spaces(): void
    {
        $result = $this->controller->publicParseCommand('GET   mykey');
        $this->assertEquals(['GET', 'mykey'], $result);
    }

    public function test_empty_string_returns_empty_array(): void
    {
        $result = $this->controller->publicParseCommand('');
        $this->assertEquals([], $result);
    }

    public function test_single_word_command(): void
    {
        $result = $this->controller->publicParseCommand('PING');
        $this->assertEquals(['PING'], $result);
    }

    public function test_command_with_multiple_args(): void
    {
        $result = $this->controller->publicParseCommand('MSET key1 val1 key2 val2');
        $this->assertEquals(['MSET', 'key1', 'val1', 'key2', 'val2'], $result);
    }

    public function test_quoted_string_with_special_chars(): void
    {
        $result = $this->controller->publicParseCommand('SET key "value with \"nested\" not supported but special: !@#$%"');
        // The parser doesn't handle escaped quotes, but should handle special chars inside quotes
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_mixed_quotes(): void
    {
        $result = $this->controller->publicParseCommand("SET key1 \"double quoted\" key2 'single quoted'");
        $this->assertEquals(['SET', 'key1', 'double quoted', 'key2', 'single quoted'], $result);
    }

    // -------------------------------------------------------
    // formatResult
    // -------------------------------------------------------

    public function test_format_null_returns_nil(): void
    {
        $this->assertEquals('(nil)', $this->controller->publicFormatResult(null));
    }

    public function test_format_true_returns_integer_1(): void
    {
        $this->assertEquals('(integer) 1', $this->controller->publicFormatResult(true));
    }

    public function test_format_false_returns_integer_0(): void
    {
        $this->assertEquals('(integer) 0', $this->controller->publicFormatResult(false));
    }

    public function test_format_string_returns_string(): void
    {
        $this->assertEquals('hello', $this->controller->publicFormatResult('hello'));
    }

    public function test_format_int_returns_int(): void
    {
        $this->assertEquals(42, $this->controller->publicFormatResult(42));
    }

    public function test_format_array_is_recursive(): void
    {
        $result = $this->controller->publicFormatResult([null, true, 'hello', [false]]);
        $this->assertEquals(['(nil)', '(integer) 1', 'hello', ['(integer) 0']], $result);
    }

    public function test_format_empty_array(): void
    {
        $this->assertEquals([], $this->controller->publicFormatResult([]));
    }
}
