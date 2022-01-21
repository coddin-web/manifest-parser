<?php

declare(strict_types=1);

namespace Coddin\ManifestParser;

final class ManifestData
{
    private bool $hasData = false;
    /** @var array<string> */
    private array $scripts;
    /** @var array<string> */
    private array $styleSheets;
    private ?string $errorMessage;

    /**
     * @param array<string> $scripts
     * @param array<string> $styleSheets
     */
    private function __construct(
        array $scripts,
        array $styleSheets,
        ?string $errorMessage = null
    ) {
        $this->scripts = $scripts;
        $this->styleSheets = $styleSheets;
        $this->errorMessage = $errorMessage;

        if (!empty($scripts)) {
            $this->hasData = true;
        }
    }

    /**
     * @param array<string> $scripts
     * @param array<string> $styleSheets
     */
    public static function create(
        array $scripts = [],
        array $styleSheets = []
    ): self {
        return new self($scripts, $styleSheets);
    }

    public static function error(string $message): self
    {
        return new self([], [], $message);
    }

    public function hasData(): bool
    {
        return $this->hasData;
    }

    /**
     * @return array<string>
     */
    public function getScripts(): array
    {
        return $this->scripts;
    }

    /**
     * @return array<string>
     */
    public function getStyleSheets(): array
    {
        return $this->styleSheets;
    }

    public function hasError(): bool
    {
        return $this->errorMessage !== null;
    }

    public function getError(): string
    {
        return ($this->errorMessage ?? '');
    }
}
