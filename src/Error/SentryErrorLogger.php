<?php
declare(strict_types=1);

namespace App\Error;

use Cake\Core\Configure;
use Cake\Error\ErrorLogger;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class SentryErrorLogger extends ErrorLogger
{
    /**
     * Capture an exception with Sentry
     */
    public function log(Throwable $exception, ?ServerRequestInterface $request = null): bool
    {
        if (!Configure::read('debug')) {
            \Sentry\captureException($exception);
        }

        return parent::log($exception, $request);
    }

    /**
     * Capture an error message with Sentry.
     */
    public function logMessage($level, string $message, array $context = []): bool
    {
        if (!Configure::read('debug')) {
            \Sentry\captureMessage($message, \Sentry\Severity::fromError($level));
        }

        return parent::logMessage($level, $message, $context);
    }
}
