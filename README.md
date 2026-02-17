# Laravel Redis Explorer

A beautiful web-based Redis Explorer for Laravel. Browse keys, run commands, and monitor your Redis instance — right from your browser.

![Redis Explorer](https://img.shields.io/badge/Laravel-10%20|%2011%20|%2012-red) ![PHP](https://img.shields.io/badge/PHP-8.1+-blue)

## Features

- 🖥️ **CLI Interface** – Run any Redis command from a terminal-like UI
- 🔑 **Key Browser** – Browse keys with SCAN, see types and TTLs
- 📊 **Server Info** – View Redis version, memory, clients, hit rate, and more
- 🕐 **Command History** – Arrow keys navigate history, persisted in localStorage
- ⚠️ **Safety** – Dangerous commands require confirmation, some are blocked entirely
- 🔌 **Multi-connection** – Switch between Redis connections from the UI
- 🎨 **Beautiful Dark UI** – Premium dark theme with JetBrains Mono font
- ⚙️ **Configurable** – Custom path, middleware, blocked commands

## Installation

```bash
composer require pawell67/laravel-redis-explorer
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=redis-explorer-config
```

### Config Options

| Option | Default | Description |
|--------|---------|-------------|
| `path` | `redis-explorer` | URL path for the explorer |
| `middleware` | `['web']` | Route middleware |
| `connection` | `default` | Default Redis connection |
| `dangerous_commands` | `[FLUSHDB, ...]` | Commands that show a warning |
| `blocked_commands` | `[SHUTDOWN, DEBUG]` | Commands that are blocked |

### Environment Variables

```env
REDIS_EXPLORER_PATH=redis-explorer
REDIS_EXPLORER_MIDDLEWARE=web
REDIS_EXPLORER_CONNECTION=default
```

## Usage

Navigate to `http://your-app.test/redis-explorer` and start running commands.

### Securing in Production

Add authentication middleware in your config:

```php
'middleware' => ['web', 'auth'],
```

Or use a gate/policy — the package respects whatever middleware you configure.

## License

MIT
