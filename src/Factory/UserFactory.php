<?php

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(private PasswordHasherFactoryInterface $passwordHasherFactory)
    {
    }

    #[\Override]
    public static function class(): string
    {
        return User::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'email' => self::faker()->email(),
            'plainPassword' => self::faker()->password(),
            'roles' => [],
            'name' => self::faker()->name()
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function(User $user): void {
                $plainPassword = $user->getPlainPassword();
                if (!$plainPassword) {
                   return;
                }
                $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user);
                $user->setPassword($passwordHasher->hash($plainPassword));
                $user->setPlainPassword(null);
            })
        ;
    }
}
