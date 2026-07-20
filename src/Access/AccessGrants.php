<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Access;

/**
 * Object-level гранты через ACL ядра — замена собственным pivot-таблицам доступа
 * («user может объект X»). Гранты хранятся как политики ядра, попадают в аудит
 * ACL, работают роли и wildcard.
 *
 * Ограничение безопасности (enforce хостом): все resource/prefix обязаны
 * начинаться с 'ext.{id}.' текущего расширения — оно не может управлять доступом
 * к ресурсам ядра или чужих расширений.
 */
interface AccessGrants
{
    public function grantToUser(int $userId, string $resource, string $action = CapabilityAction::ACCESS): void;

    public function revokeFromUser(int $userId, string $resource, string $action = CapabilityAction::ACCESS): void;

    /**
     * Полностью убрать object-level доступ к ресурсам у ВСЕХ субъектов
     * (идемпотентно). Wildcard на родительский префикс не затрагивается.
     *
     * @param  list<string>  $resources
     */
    public function revokeResourceFromAll(array $resources, string $action = CapabilityAction::ACCESS): void;

    /**
     * Заменить полный набор грантов пользователя в пределах префикса.
     *
     * @param  list<string>  $resources
     */
    public function replaceUserGrants(int $userId, string $resourcePrefix, array $resources, string $action = CapabilityAction::ACCESS): void;

    public function userCan(int $userId, string $resource, string $action = CapabilityAction::ACCESS): bool;

    /**
     * Batch-проверка одним запросом (для списков).
     *
     * @param  list<string>  $resources
     * @return array<string, bool> resource => allowed
     */
    public function userCanBatch(int $userId, array $resources, string $action = CapabilityAction::ACCESS): array;

    /**
     * Прямые пользовательские гранты под префиксом (без ролей/wildcard).
     *
     * @return array<int, list<string>> userId => resources
     */
    public function userGrantsByPrefix(string $resourcePrefix, string $action = CapabilityAction::ACCESS): array;
}
