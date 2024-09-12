<?php

namespace App\Contracts;

interface IconRepositoryInterface
{
    public function getTotalIconCount(): int;

    public function downloadAndExtractRepo(): void;

    public function generateMetadata(): void;

    public function processIcon(array $iconData): void;

    public function getIconVariants(): array;

    public function cleanup(): void;
}
