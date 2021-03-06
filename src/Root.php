<?php

declare(strict_types=1);

namespace Wtf;

use Psr\Container\ContainerInterface;

class Root
{
    /**
     * PSR-11 Container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Storage for magic getter/setter.
     *
     * @var array
     */
    protected $data = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Call method or getter/setter for property.
     *
     * @param string $method
     * @param array  $params
     *
     * @throws \Exception if method not implemented in class
     *
     * @return mixed Data from object property
     */
    public function __call(?string $method = null, array $params = [])
    {
        $parts = \preg_split('/([A-Z][^A-Z]*)/', $method, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $type = \array_shift($parts);

        // Call current class method
        if (\method_exists($this, $method)) {
            return \call_user_func_array([$this, $method], $params);
        }

        // Call method from container
        if ($this->container->has($method)) {
            return \call_user_func_array($this->container[$method], $params);
        }

        // Call getter/setter
        if ('get' === $type || 'set' === $type) {
            $property = \strtolower(\implode('_', $parts));
            $params = (isset($params[0])) ? [$property, $params[0]] : [$property];

            return \call_user_func_array([$this, $type], $params);
        }

        throw new \Exception('Method "'.$method.'" not implemented.');
    }

    /**
     * Magic get from container.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }

        return null;
    }

    /**
     * Get property data, eg get('post_id').
     *
     * @param string $property
     * @param mixed  $default  Default value if property not exists
     *
     * @return mixed
     */
    public function get(string $property, $default = null)
    {
        return $this->data[$property] ?? $default;
    }

    /**
     * Set property data, eg set('post_id',1).
     *
     * @param string $property
     * @param mixed  $data
     *
     * @return $this
     */
    public function set(string $property, $data = null): self
    {
        $this->data[$property] = $data;

        return $this;
    }
}
