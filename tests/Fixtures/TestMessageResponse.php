<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Mpociot\ApiDoc\Transformers\ResponseApiDataAbstract;

class TestMessageResponse extends ResponseApiDataAbstract
{
    /**
     * {@inheritDoc}
     */
    public function response()
    {
        return [
            'message' => $this->message,
            'status_code' => $this->status,
        ];
    }
}