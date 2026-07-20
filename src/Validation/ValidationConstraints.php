<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Validation;

/**
 * Контракт доступа к правилам валидации платформы. Правила — собственность ЯДРА
 * (config); хост биндит реализацию и инжектит расширению по type-hint. В SDK —
 * только контракт и VO, без правил/мутабельной статики.
 */
interface ValidationConstraints
{
    public function password(): PasswordConstraint;

    public function email(): EmailConstraint;

    public function slug(): PatternConstraint;

    public function aclAction(): PatternConstraint;

    public function roleCode(): PatternConstraint;
}
