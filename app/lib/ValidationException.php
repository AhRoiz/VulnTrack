<?php

declare(strict_types=1);

final class ValidationException extends RuntimeException
{
    public function __construct(public readonly array $errors, string $message = 'Data yang dikirim tidak valid.')
    {
        parent::__construct($message);
    }

    public function first(): string
    {
        foreach ($this->errors as $msg) {
            return (string) $msg;
        }
        return $this->getMessage();
    }
}

