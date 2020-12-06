<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

/**
 * @group 1. Group 1
 *
 * Group 1 APIs
 */
class TestGroupController
{
    /**
     * Some endpoint.
     *
     * By default, this is in Group 1.
     */
    public function action1()
    {
    }

    /**
     * Another endpoint.
     *
     * Here we specify a group. This is also in Group 1.
     *
     * @group 1. Group 1
     */
    public function action1b()
    {
    }

    /**
     * @group 2. Group 2
     */
    public function action2()
    {
    }

    /** @group 10. Group 10 */
    public function action10()
    {
    }
}
