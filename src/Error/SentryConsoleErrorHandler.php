<?php
declare(strict_types=1);

namespace App\Error;

use Cake\Core\Configure;
use Cake\Error\ConsoleErrorHandler;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Error handler that forwards events to sentry.
 */
class SentryConsoleErrorHandler extends ConsoleErrorHandler
{
    protected function _logError($level, array $data): bool
    {
        if (!Configure::read('debug')) {
            $message = sprintf(
                '%s (%s): %s in [%s, line %s]',
                $data['error'],
                $data['code'],
                $data['description'],
                $data['file'],
                $data['line']
            );
            \Sentry\captureMessage($message);
        }

        return parent::_logError($level, $data);
    }

    /**
     * Log an error for the exception if applicable.
     *
     * @param \Throwable $exception The exception to log a message for.
     * @param \Psr\Http\Message\ServerRequestInterface $request The current request.
     * @return bool
     */
    public function logException(Throwable $exception, ?ServerRequestInterface $request = null): bool
    {
        // Duplicating logic in ErrorLogger as there isn't a good way to re-use it.
        foreach ((array)$this->getConfig('skipLog') as $class) {
            if ($exception instanceof $class) {
                return false;
            }
        }

        if (!Configure::read('debug')) {
            \Sentry\captureException($exception);
        }

        return parent::logException($exception, $request);
    }
}
