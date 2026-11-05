<?php
/**
 * ValidationException - membawa daftar error validasi per-field.
 */

declare(strict_types=1);

final class ValidationException extends RuntimeException
{
    public function __construct(public readonly array $errors, string $message = 'Data yang dikirim tidak valid.')
    {
        parent::__construct($message);
    }
}

