<?php

namespace Mpociot\ApiDoc\Tests;

use Mockery as m;
use Orchestra\Testbench\TestCase;
use Illuminate\Translation\Translator;
use Illuminate\Translation\LoaderInterface;
use Mpociot\ApiDoc\Parsers\RuleDescriptionParser;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;

class RuleDescriptionParserTest extends TestCase
{
    protected $translatorMock;

    public function setUp()
    {
        parent::setUp();
        $fileLoaderMock = m::mock(LoaderInterface::class);
        $this->translatorMock = m::mock(Translator::class, [$fileLoaderMock, 'es']);
        $this->app->instance('translator', $this->translatorMock);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testReturnsAnEmptyDescriptionIfARuleIsNotParsed()
    {
        $this->translatorMock->shouldReceive('hasForLocale')->twice()->andReturn(false);

        $description = new RuleDescriptionParser();

        $this->assertEmpty($description->getDescription());
    }

    public function testProvidesANamedContructor()
    {
        $this->assertInstanceOf(RuleDescriptionParser::class, RuleDescriptionParser::parse());
    }

    public function testReturnsADescriptionInMainLanguageIfAvailable()
    {
        $this->translatorMock->shouldReceive('hasForLocale')->twice()->with('apidoc::rules.alpha')->andReturn(true);
        $this->translatorMock->shouldReceive('get')->once()->with('apidoc::rules.alpha')->andReturn('Solo caracteres alfabeticos permitidos');

        $description = RuleDescriptionParser::parse('alpha')->getDescription();

        $this->assertEquals('Solo caracteres alfabeticos permitidos', $description);
    }

    public function testReturnsDescriptionInDefaultLanguageIfNotAvailableInMainLanguage()
    {
        $this->translatorMock->shouldReceive('hasForLocale')->twice()->with('apidoc::rules.alpha')->andReturn(false);
        $this->translatorMock->shouldReceive('hasForLocale')->once()->with('apidoc::rules.alpha', 'en')->andReturn(true);
        $this->translatorMock->shouldReceive('get')->once()->with('apidoc::rules.alpha', [], 'en')->andReturn('Only alphabetic characters allowed');

        $description = RuleDescriptionParser::parse('alpha')->getDescription();

        $this->assertEquals('Only alphabetic characters allowed', $description);
    }

    public function testReturnsAnEmptyDescriptionIfNotAvailable()
    {
        $this->translatorMock->shouldReceive('hasForLocale')->once()->with('apidoc::rules.dummy_rule')->andReturn(false);
        $this->translatorMock->shouldReceive('hasForLocale')->once()->with('apidoc::rules.dummy_rule', 'en')->andReturn(false);

        $description = RuleDescriptionParser::parse('dummy_rule')->getDescription();

        $this->assertEmpty($description);
    }

    public function testAllowsToPassParametersToTheDescription()
    {
        $this->translatorMock->shouldReceive('hasForLocale')->twice()->with('apidoc::rules.digits')->andReturn(false);
        $this->translatorMock->shouldReceive('hasForLocale')->once()->with('apidoc::rules.digits', 'en')->andReturn(true);
        $this->translatorMock->shouldReceive('get')->once()->with('apidoc::rules.digits', [], 'en')->andReturn('Must have an exact length of `:attribute`');

        $description = RuleDescriptionParser::parse('digits')->with(2)->getDescription();

        $this->assertEquals('Must have an exact length of `2`', $description);
    }

    public function testAllowsToPassMultipleParametersToTheDescription()
    {
        $this->translatorMock->shouldReceive('hasForLocale')->twice()->with('apidoc::rules.required_if')->andReturn(false);
        $this->translatorMock->shouldReceive('hasForLocale')->once()->with('apidoc::rules.required_if', 'en')->andReturn(true);
        $this->translatorMock->shouldReceive('get')->once()->with('apidoc::rules.required_if', [], 'en')->andReturn('Required if `:attribute` is `:attribute`');

        $description = RuleDescriptionParser::parse('required_if')->with(['2 + 2', 4])->getDescription();

        $this->assertEquals('Required if `2 + 2` is `4`', $description);
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
     * @param  \Illuminate\Foundation\Application   $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.locale', 'es'); // Just to be different to default language.
        $app['config']->set('app.fallback_locale', 'ch'); // Just to be different to default language.
    }
}
