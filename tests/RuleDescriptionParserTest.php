<?php

namespace Mpociot\ApiDoc\Tests;

use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;
use Mpociot\ApiDoc\Parsers\RuleDescriptionParser;
use Orchestra\Testbench\TestCase;

class RuleDescriptionParserTest extends TestCase
{
    public function testReturnsAnEmptyDescriptionIfARuleIsNotParsed()
    {
        $rule = new RuleDescriptionParser();

        $this->assertEmpty($rule->getDescription());
    }

    public function testProvidesANamedContructor()
    {
        $this->assertInstanceOf(RuleDescriptionParser::class, RuleDescriptionParser::parse());
    }

    public function testReturnsADescriptionInTheMainLanguageOfTheApplication()
    {
        $expected = 'Only alphabetic characters allowed';
        $rule = new RuleDescriptionParser('alpha');

        $this->assertEquals($expected, $rule->getDescription());
    }

    public function testReturnsAnEmptyDescriptionIfNotAvailable()
    {
        $rule = new RuleDescriptionParser('dummy_rule');

        $description = $rule->getDescription();

        $this->assertEmpty($description);
    }

    public function testAllowsToPassParametersToTheDescription()
    {
        $expected = 'Must have an exact length of `2`';
        $rule = new RuleDescriptionParser('digits');

        $actual = $rule->with(2)->getDescription();

        $this->assertEquals($expected, $actual);
    }

    public function testOnlyPassesParametersIfTheDescriptionAllows()
    {
        $expected = 'Only alphabetic characters allowed';
        $rule = new RuleDescriptionParser('alpha');

        $actual = $rule->with('dummy parameter')->getDescription();

        $this->assertEquals($expected, $actual);
    }

    public function testAllowsToPassMultipleParametersToTheDescription()
    {
        $expected = 'Required if `2 + 2` is `4`';
        $rule = new RuleDescriptionParser('required_if');

        $actual = $rule->with(['2 + 2', 4])->getDescription();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [ApiDocGeneratorServiceProvider::class];
    }
    
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.locale', 'en');
    }
}
