<?php

namespace App\Contracts;

interface IconRepositoryInterface
{
    public function fetchMetadata(): array;

    public function downloadAndExtractRepo(): void;

    public function getIconVariants(): array;

    public function processIcon(array $iconData): void;

    public function cleanup(): void;
}
