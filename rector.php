<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__
    ]);
    $parameters->set(Option::SKIP, [
        __DIR__ . '/vendor'
    ]);
    $containerConfigurator->import(SetList::PHP_74);

    // Define what rule sets will be applied
    $containerConfigurator->import(SetList::CODE_QUALITY);

    // get services (needed for register a single rule)
    $services = $containerConfigurator->services();

    $services->set(TypedPropertyRector::class);
};
