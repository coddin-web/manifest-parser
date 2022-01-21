<?php

declare(strict_types=1);

namespace Coddin\ManifestParser\Exception;

final class ManifestDataException extends \Exception
{
    public static function create(string $message, \Throwable $previous = null): self
    {
        return new self($message, 0, $previous);
    }
}
