<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use League\Fractal\TransformerAbstract;

class TestMessageTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform($data)
    {
        return [
            'message' => $data->message,
            'status_code' => $data->status_code,
        ];
    }
}
