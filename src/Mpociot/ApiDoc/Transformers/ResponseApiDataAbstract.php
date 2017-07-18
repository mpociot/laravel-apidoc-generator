<?php

namespace Mpociot\ApiDoc\Transformers;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

abstract class ResponseApiDataAbstract implements ArrayAccess, Arrayable, Jsonable, JsonSerializable
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $data;

    /**
     * CustomDataResponseTransformer constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->setData($data);
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->getData()->offsetExists($key);
    }

    /**
     * Get Custom Data.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getData()
    {
        return $this->data;
    }

    /**
     * Set Custom Data.
     *
     * @param array $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = collect($data);

        return $this;
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->{$key};
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->getData()->offsetSet($key, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->getData()->offsetUnset($key);
    }

    /**
     * Get value by name from data collection.
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getData()->get($name) ?? data_get($this->response(), $name);
    }

    /**
     * Set new value for data collection.
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->getData()->offsetSet($name, $value);
    }

    /**
     * Get response data to pass to transformers.
     *
     * @return array
     */
    abstract public function response();

    /**
     * Get response as object.
     *
     * @return object
     */
    public function toObject()
    {
        return (object) $this->response();
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->response());
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof Jsonable) {
                return json_decode($value->toJson(), true);
            } elseif ($value instanceof Arrayable) {
                return $value->toArray();
            } else {
                return $value;
            }
        }, $this->response());
    }

    /**
     * Handle dynamic data collection method
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (! method_exists($this, $name)) {
            return $this->getData()->$name(...$arguments);
        }

        return $this->$name(...$arguments);
    }
}