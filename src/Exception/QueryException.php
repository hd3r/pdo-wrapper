<?php

declare(strict_types=1);

namespace PdoWrapper\Exception;

use Exception;
use Throwable;

/**
 * Thrown when a database query fails.
 */
class QueryException extends Exception
{
    /**
     * @param string $message User-friendly error message
     * @param int $code Error code
     * @param Throwable|null $previous Previous exception
     * @param string|null $debugMessage Detailed debug information
     */
    public function __construct(
        string $message = 'Query failed',
        int $code = 0,
        ?Throwable $previous = null,
        private ?string $debugMessage = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get detailed debug message.
     *
     * @return string|null Debug information (e.g., SQL query, PDO error)
     */
    public function getDebugMessage(): ?string
    {
        return $this->debugMessage;
    }
}
