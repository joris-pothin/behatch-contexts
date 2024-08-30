<?php
declare(strict_types=1);

namespace Behatch\Context\ContextClass;

use Behat\Behat\Context\ContextClass\ClassResolver as BaseClassResolver;

class ClassResolver implements BaseClassResolver
{
    public function supportsClass($contextClass): bool
    {
        return \str_starts_with($contextClass, 'behatch:context:');
    }

    public function resolveClass($contextClass): string
    {
        $className = \preg_replace_callback(
            '/(^\w|:\w)/',
            static fn($matches) => \str_replace(':', '\\', \strtoupper((string) $matches[0])),
            $contextClass
        );

        return $className . 'Context';
    }
}
