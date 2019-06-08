<?php

namespace Allex;

abstract class Abstract_Service
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * TextDomain constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return Container
     */
    public function get_container()
    {
        return $this->container;
    }
}

