<?php

namespace PHPCensor\Plugin;

use PHPCensor\Plugin;

/**
 * Points "php" at another binary for commands in this build.
 * Builder restores the worker PATH after the build finishes {@see \PHPCensor\Builder::restoreProcessPath()}.
 *
 * @package    PHP Censor
 * @subpackage Application
 *
 * @author yugeon
 */
class PhpBinary extends Plugin
{
    private const WRAPPER_DIRECTORY = '.php-bin';

    /**
     * @return string
     */
    public static function pluginName()
    {
        return 'php_binary';
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $binary = $this->binary();
        if ($binary === null) {
            $this->builder->logFailure('PHP binary is missing or not executable.');

            return false;
        }

        $directory = \rtrim($this->builder->buildPath, '/\\') . '/' . self::WRAPPER_DIRECTORY;
        if ($directory === '/' . self::WRAPPER_DIRECTORY) {
            $this->builder->logFailure('Build path is not available.');

            return false;
        }

        if (!\is_dir($directory) && !\mkdir($directory, 0755, true) && !\is_dir($directory)) {
            $this->builder->logFailure('Unable to create PHP binary directory.');

            return false;
        }

        $script = "#!/bin/sh\nexec " . \escapeshellarg($binary) . " \"$@\"\n";
        $wrapper = $directory . '/php';
        if (\file_put_contents($wrapper, $script) === false || !\chmod($wrapper, 0755)) {
            $this->builder->logFailure('Unable to write PHP wrapper.');

            return false;
        }

        if (!\putenv('PATH=' . $this->pathWithWrapper($directory))) {
            $this->builder->logFailure('Unable to set PATH.');

            return false;
        }

        if (!$this->builder->executeCommand('php -v')) {
            $this->builder->logFailure('Unable to run the selected PHP binary.');

            return false;
        }

        $version = $this->builder->getLastOutput();
        if ($version !== '') {
            $this->builder->log($version);
        }

        return true;
    }

    /**
     * Put the wrapper directory first on the current PATH.
     * Repeated calls may leave the same directory later in the list. The shell uses the first match.
     * $_SERVER['PATH'] is only a fallback when the process has no PATH yet.
     */
    private function pathWithWrapper(string $directory): string
    {
        $current = \getenv('PATH');
        if (!\is_string($current) || $current === '') {
            $current = isset($_SERVER['PATH']) && \is_string($_SERVER['PATH']) ? $_SERVER['PATH'] : '';
        }

        if ($current === '') {
            return $directory;
        }

        return $directory . \PATH_SEPARATOR . $current;
    }

    private function binary(): ?string
    {
        if (!isset($this->options['binary']) || !\is_string($this->options['binary'])) {
            return null;
        }

        $binary = $this->options['binary'];
        if (!\preg_match('#\\A/[^\\x00\\r\\n]*\\z#', $binary) || !\is_file($binary) || !\is_executable($binary)) {
            return null;
        }

        return $binary;
    }
}
