<?php

namespace jakumi\SymfonySes\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader;
use Aws\Ses\SesClient;

class SymfonySesExtension extends Extension {
    public function load(array $configs, ContainerBuilder $container) {

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $container->setAlias('swiftmailer.mailer.transport.ses', 'jakumi\SymfonySes\SesTransport');
    }
}