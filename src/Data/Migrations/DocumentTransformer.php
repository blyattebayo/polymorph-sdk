<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data\Migrations;

interface DocumentTransformer
{
    /** @param array<string,mixed> $document @param array<string,mixed> $operation @return array<string,mixed> */
    public function transform(array $document, array $operation): array;
}
