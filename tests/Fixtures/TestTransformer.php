<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use League\Fractal\TransformerAbstract;

/**
 * A test transformer to show the functions.
 *
 * @author Tobias van Beek <t.vanbeek@tjvb.nl>
 */
class TestTransformer extends TransformerAbstract
{
    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(TestModel $model)
    {
        return [
            'id' => $model->id,
            'description' => $model->description,
            'name' => $model->name,
        ];
    }
}
