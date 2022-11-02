<?php
namespace LesserEvil;

class ShellVerbosityIsEvil
{

    /**
     * Adapter for `Application::configureIO()` which disables SHELL_VERBOSITY.
     *
     * @param callable $callback
     *
     * @return mixed
     *   The value returned by $callback.
     */
    public static function doWithoutEvil($callback)
    {
        try {
            static::removeEvil(); // Hear no evil.
            return $callback();
        } finally {
            static::removeEvil(); // Speak no evil.
        }
    }

    public static function removeEvil(): void
    {
        if (\function_exists('putenv')) {
            @putenv('SHELL_VERBOSITY=');
        }
        unset($_ENV['SHELL_VERBOSITY']);
        unset($_SERVER['SHELL_VERBOSITY']);
    }

}
