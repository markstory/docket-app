<?php
declare(strict_types=1);

namespace App\Error;

use Cake\Error\ErrorLogger;
use Cake\Error\PhpError;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

/**
 * Log errors and exceptions as Sentry events.
 */
class SentryErrorLogger extends ErrorLogger
{
    /**
     * Capture an exception with Sentry
     */
    public function logException(
        Throwable $exception,
        ?ServerRequestInterface $request = null,
        bool $includeTrace = false
    ): void {
        \Sentry\captureException($exception);

        parent::logException($exception, $request, $includeTrace);
    }

    /**
     * Capture an error message with Sentry.
     */
    public function logError(PhpError $error, ?ServerRequestInterface $request = null, bool $includeTrace = false): void
    {
        \Sentry\captureMessage($error->getMessage(), \Sentry\Severity::fromError($error->getCode()));

        parent::logError($error, $request, $includeTrace);
    }

    /**
     * Capture an exception with Sentry
     */
    public function log(Throwable $exception, ?ServerRequestInterface $request = null): bool
    {
        throw new RuntimeException('This method should not be called anymore.');
    }

    /**
     * Capture an error message with Sentry.
     */
    public function logMessage($level, string $message, array $context = []): bool
    {
        throw new RuntimeException('This method should not be called anymore.');
    }
}
