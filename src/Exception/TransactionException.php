<?php

declare(strict_types=1);

namespace PdoWrapper\Exception;

use Exception;
use Throwable;

class TransactionException extends Exception
{
    public function __construct(
        string $message = 'Transaction failed',
        int $code = 0,
        ?Throwable $previous = null,
        private ?string $debugMessage = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getDebugMessage(): ?string
    {
        return $this->debugMessage;
    }
}
