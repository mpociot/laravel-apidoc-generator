<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use League\Fractal\TransformerAbstract;

/**
 * A test transformer to show the functions.
 *
 * @author Tobias van Beek <t.vanbeek@tjvb.nl>
 */
class TestTransformerUseData extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform($data)
    {
        return [
            'id' => $data->id,
            'description' => $data->description,
            'name' => $data->name,
        ];
    }
}
