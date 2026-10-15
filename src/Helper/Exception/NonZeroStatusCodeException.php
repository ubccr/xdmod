<?php declare(strict_types=1);

namespace CCR\Helper\Exception;

use RuntimeException;
use Throwable;

class NonZeroStatusCodeException extends RuntimeException
{
    public string $output;

    public function __construct(string $output = "", string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        $this->output = $output;

        parent::__construct($message, $code, $previous);
    }
}
