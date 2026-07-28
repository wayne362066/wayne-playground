<?php

namespace App\Modules\Lottery\Exceptions;

use RuntimeException;

final class DuelException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 409,
    ) {
        parent::__construct($message);
    }
}
