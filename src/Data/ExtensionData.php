<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/**
 * Удобный СКОУПНУТЫЙ на расширение доступ к репозиториям по имени сущности.
 *
 * Под капотом — per-entity {@see Repository} (как и задумано в ADR), но сервис,
 * работающий с несколькими сущностями, держит одну зависимость и берёт нужный
 * репозиторий по имени: `$this->data->repository('attempts')->query()...`.
 */
interface ExtensionData
{
    /**
     * @param class-string<Entity> $entityClass опц. сгенерированный подкласс (codegen)
     * @return Repository<Entity>
     */
    public function repository(string $entity, string $entityClass = Entity::class): Repository;
}
