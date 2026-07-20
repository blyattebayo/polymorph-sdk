<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/**
 * Кардинальность поля: одно значение или коллекция.
 */
enum Cardinality: string
{
    case ONE = 'one';
    case MANY = 'many';
}
