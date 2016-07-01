<?php

namespace Mpociot\ApiDoc\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\App;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;
use Mpociot\ApiDoc\Parsers\RuleDescriptionParser;
use Orchestra\Testbench\TestCase;

class RuleDescriptionParserTest extends TestCase
{
    const LANG_PATH = __DIR__.'/../src/resources/lang';

    const LANG_TEST_PATH = __DIR__.'/fixtures/resources/lang';

    public function testReturnsAnEmptyDescriptionIfARuleIsNotParsed()
    {
        $rule = new RuleDescriptionParser();

        $this->assertEmpty($rule->getDescription());
    }

    public function testProvidesANamedContructor()
    {
        $this->assertInstanceOf(RuleDescriptionParser::class, RuleDescriptionParser::parse());
    }

    public function testReturnsADescriptionInDefaultLanguage()
    {
        $expected = 'Only alphabetic characters allowed';
        $rule = new RuleDescriptionParser('alpha');

        $this->assertEquals($expected, $rule->getDescription());
    }

    public function testReturnsADescriptionInMainLanguageIfAvailable()
    {
        $file = new Filesystem();
        $file->copyDirectory(self::LANG_TEST_PATH, self::LANG_PATH);
        App::setLocale('es');

        $actual = RuleDescriptionParser::parse('alpha')->getDescription();

        $file->deleteDirectory(self::LANG_PATH.'/es');
        $this->assertEquals('Solo caracteres alfabeticos permitidos', $actual);
    }

    public function testReturnsDescriptionInDefaultLanguageIfNotAvailableInMainLanguage()
    {
        $file = new Filesystem();
        $file->copyDirectory(self::LANG_TEST_PATH, self::LANG_PATH);
        App::setLocale('es');

        $actual = RuleDescriptionParser::parse('alpha_num')->getDescription();

        $file->deleteDirectory(self::LANG_PATH.'/es');
        $this->assertEquals('Only alpha-numeric characters allowed', $actual);
    }

    public function testReturnsAnEmptyDescriptionIfNotAvailable()
    {
        $rule = new RuleDescriptionParser('dummy_rule');

        $description = $rule->getDescription();

        $this->assertEmpty($description);
    }

    public function testAllowsToPassParametersToTheDescription()
    {
        $rule = new RuleDescriptionParser('digits');

        $actual = $rule->with(2)->getDescription();

        $this->assertEquals('Must have an exact length of `2`', $actual);
    }

    public function testOnlyPassesParametersIfTheDescriptionAllows()
    {
        $rule = new RuleDescriptionParser('alpha');

        $actual = $rule->with('dummy parameter')->getDescription();

        $this->assertEquals('Only alphabetic characters allowed', $actual);
    }

    public function testAllowsToPassMultipleParametersToTheDescription()
    {
        $rule = new RuleDescriptionParser('required_if');

        $actual = $rule->with(['2 + 2', 4])->getDescription();

        $this->assertEquals('Required if `2 + 2` is `4`', $actual);
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
        $app['config']->set('app.fallback_locale', 'ch'); // Just to be different to default language.
    }
}
