<?php

namespace Omnik\Core\Model;

use Omnik\Core\Api\ConfigInterface;

class Config implements ConfigInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * Config constructor.
     *
     * @param null $params
     */
    public function __construct($params = null)
    {
        if ($params && is_array($params)) {
            foreach ($params as $key => $param) {
                $this->data[$key] = $param;
            }
        }

        return $this;
    }

    /**
     * Get all configurations set
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get a specific key value.
     *
     * @param string $key key to retrieve
     *
     * @return mixed|null Value of the key or NULL
     */
    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Set a key value pair
     *
     * @param string $key   Key to set
     * @param mixed  $value Value to set
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
}
