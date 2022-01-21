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

    /**
     * @param array<string> $scripts
     * @param array<string> $styleSheets
     */
    private function __construct(
        array $scripts,
        array $styleSheets
    ) {
        $this->scripts = $scripts;
        $this->styleSheets = $styleSheets;

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
}
