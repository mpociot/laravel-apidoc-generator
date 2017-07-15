<?php

namespace Mpociot\ApiDoc\Transformers;

use function collect;

abstract class ResponseApiDataAbstract
{
    /**
     * @var \Illuminate\Support\Collection $data
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
     * Get value by name from data collection
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getData()->get($name);
    }

    /**
     * Set new value for data collection
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->getData()[$name] = $value;
    }

    /**
     * Get Custom Data
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getData()
    {
        return $this->data;
    }

    /**
     * Set Custom Data
     *
     * @param array $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = collect($data);

        return $this;
    }

    /**
     * Get response as object
     *
     * @return object
     */
    public function toObject()
    {
        return (object) $this->response();
    }

    /**
     * Get response data to pass to transformers
     *
     * @return array
     */
    abstract public function response();
}