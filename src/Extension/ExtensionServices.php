<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Extension;

use Polymorph\Sdk\Access\AccessGrants;
use Polymorph\Sdk\Data\DefinitionRegistry;
use Polymorph\Sdk\Data\Entity;
use Polymorph\Sdk\Data\ExtensionData;
use Polymorph\Sdk\Data\Repository;

/**
 * Фабрика SDK-сервисов, скоупнутых на расширение по ЯВНОМУ {@see ExtensionContext}.
 *
 * Это замена ambient-магии V1 (контекстные биндинги + scopedConsumers() + guard,
 * где забытый вложенный потребитель молча падал на LogicException). Здесь скоуп
 * всегда передаётся явно — нет «невидимого» состояния и footgun'а.
 */
interface ExtensionServices
{
    /**
     * @param class-string<Entity> $entityClass опц. сгенерированный подкласс (codegen)
     * @return Repository<Entity>
     */
    public function repository(ExtensionContext $context, string $entity, string $entityClass = Entity::class): Repository;

    /** Скоупнутый фасад доступа к репозиториям по имени сущности. */
    public function data(ExtensionContext $context): ExtensionData;

    public function definitions(ExtensionContext $context): DefinitionRegistry;

    public function grants(ExtensionContext $context): AccessGrants;
}
