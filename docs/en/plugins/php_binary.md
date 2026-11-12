Plugin PHP Binary
=================

Selects which `php` executable later build commands run. The worker process itself keeps the PHP it was started with.

`#!/usr/bin/env php` runs the first `php` found on `PATH`. This plugin writes a small wrapper with that name and puts its directory at the front of `PATH`. Call it each time the following plugins should use another PHP version. The worker restores its own `PATH` when the build ends, including when the build fails.

Configuration
-------------

### Options

* **binary** — Absolute path to the PHP executable, for example `/usr/bin/php8.3`. The file must exist and be executable.

### Examples

```yml
setup:
  php_binary_step:
    plugin: php_binary
    binary: /usr/bin/php8.3
  composer_step:
    plugin: composer
```

Put a step before the plugins that should use that PHP, for example Composer or PHPUnit.
