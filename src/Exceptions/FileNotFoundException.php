<?php

namespace Mpociot\ApiDoc\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class FileNotFoundException extends HttpException
{
    public function __construct(int $statusCode, string $message = null, Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        parent::__construct($statusCode, $message);
    }
}
