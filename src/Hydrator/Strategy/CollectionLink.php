<?php

namespace Laminas\ApiTools\Doctrine\QueryBuilder\Hydrator\Strategy;

use DoctrineModule\Stdlib\Hydrator\Strategy\AbstractCollectionStrategy;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\Filter\FilterChain;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\ServiceManagerAwareInterface;
use Laminas\Stdlib\Hydrator\Strategy\StrategyInterface;

/**
 * A field-specific hydrator for collections.
 *
 * @returns Link
 */
class CollectionLink extends AbstractCollectionStrategy implements
    StrategyInterface,
    ServiceManagerAwareInterface
{
    protected $serviceManager;

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    public function extract($value)
    {
        $config = $this->getServiceManager()->get('Config');
        if (!method_exists($value, 'getTypeClass')
            || !isset($config['api-tools-hal']['metadata_map'][$value->getTypeClass()->name])) {
            return;
        }

        $config = $config['api-tools-hal']['metadata_map'][$value->getTypeClass()->name];
        $mapping = $value->getMapping();

        $filter = new FilterChain();
        $filter->attachByName('WordCamelCaseToUnderscore')
            ->attachByName('StringToLower');

        $link = new Link($filter($mapping['fieldName']));
        $link->setRoute($config['route_name']);
        $link->setRouteParams(array('id' => null));

        if (isset($config['api-tools-doctrine-querybuilder-options']['filter_key'])) {
            $filterKey = $config['api-tools-doctrine-querybuilder-options']['filter_key'];
        } else {
            $filterKey = 'filter';
        }

        $link->setRouteOptions(array(
            'query' => array(
                $filterKey => array(
                    array('field' => $mapping['mappedBy'], 'type'=>'eq', 'value' => $value->getOwner()->getId()),
                ),
            ),
        ));

        return $link;
    }

    public function hydrate($value)
    {
        // Hydration is not supported for collections.
        // A call to PATCH will use hydration to extract then hydrate
        // an entity. In this process a collection will be included
        // so no error is thrown here.
    }
}
