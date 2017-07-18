<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Mpociot\ApiDoc\Transformers\ResponseApiDataAbstract;

class TestStaticMessageResponse extends ResponseApiDataAbstract
{
    /**
     * {@inheritdoc}
     */
    public function response()
    {
        return [
            'message' => 'test',
            'status_code' => '200',
        ];
    }
}
