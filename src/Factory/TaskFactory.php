<?php

namespace App\Factory;

use App\Entity\Task;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Task>
 */
final class TaskFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return Task::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        $statuses = [
            Task::STATUS_DONE,
            Task::STATUS_TODO,
            Task::STATUS_IN_PROGRESS
        ];

        return [
            'owner' => UserFactory::random(),
            'status' => $statuses[array_rand($statuses)],
            'title' => self::faker()->jobTitle(),
            'dueDate' => self::faker()->dateTime()
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Task $task): void {})
        ;
    }
}
