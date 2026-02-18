# Laravel Redis Console

A beautiful web-based Redis Console for Laravel. Browse keys, run commands, and monitor your Redis instance — right from your browser.

![Redis Console](https://img.shields.io/badge/Laravel-10%20|%2011%20|%2012-red) ![PHP](https://img.shields.io/badge/PHP-8.3+-blue)

## Features

- 🖥️ **CLI Interface** – Run any Redis command from a terminal-like UI
- 🔑 **Key Browser** – Browse keys with SCAN, see types and TTLs
- 📊 **Server Info** – View Redis version, memory, clients, hit rate, and more
- 🕐 **Command History** – Arrow keys navigate history, persisted in localStorage
- ⚠️ **Safety** – Dangerous commands require confirmation, some are blocked entirely
- 🔒 **Read-Only Mode** – Block all write commands for safe production use
- 🔌 **Multi-connection** – Switch between Redis connections from the UI
- 🎨 **Beautiful Dark UI** – Premium dark theme with JetBrains Mono font
- ⚙️ **Configurable** – Custom path, middleware, blocked commands

## Installation

```bash
composer require pawell67/laravel-redis-console
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=redis-console-config
```

### Config Options

| Option | Default | Description |
|--------|---------|-------------|
| `path` | `redis-console` | URL path for the console |
| `middleware` | `['web']` | Route middleware |
| `connection` | `default` | Default Redis connection |
| `max_db` | `15` | Max DB index in selector |
| `read_only` | `false` | Block all write commands |
| `dangerous_commands` | `[FLUSHDB, ...]` | Commands that show a warning |
| `blocked_commands` | `[SHUTDOWN, DEBUG]` | Commands that are blocked |

### Environment Variables

```env
REDIS_CONSOLE_PATH=redis-console
REDIS_CONSOLE_MIDDLEWARE=web
REDIS_CONSOLE_CONNECTION=default
REDIS_CONSOLE_MAX_DB=15
REDIS_CONSOLE_READ_ONLY=false
```

## Usage

Navigate to `http://your-app.test/redis-console` and start running commands.

### Securing in Production

Add authentication middleware in your config:

```php
'middleware' => ['web', 'auth'],
```

Or enable read-only mode to prevent any data modifications:

```env
REDIS_CONSOLE_READ_ONLY=true
```

## License

MIT
