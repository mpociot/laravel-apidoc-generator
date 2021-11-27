<?php

namespace LeonardoHipolito\ApiDoc;

use Illuminate\Support\Arr;
use Mni\FrontYAML\Parser;
use Windwalker\Renderer\BladeRenderer;

/**
 * Class Documentarian
 * @package Mpociot\Documentarian
 */
class Documentarian
{

    /**
     * Returns a config value
     *
     * @param string $key
     * @return mixed
     */
    public function config($folder, $key = null)
    {
        $config = include($folder . '/source/config.php');

        return is_null($key) ? $config : Arr::get($config, $key);
    }

    /**
     * Create a new API documentation folder and copy all needed files/stubs
     *
     * @param $folder
     */
    public function create($folder)
    {
        $folder = $folder . '/source';
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
            mkdir($folder . '/../css');
            mkdir($folder . '/../js');
            mkdir($folder . '/includes');
            mkdir($folder . '/assets');
        }

        // copy stub files
        copy(__DIR__ . '/../resources/stubs/index.md', $folder . '/index.md');
        copy(__DIR__ . '/../resources/stubs/gitignore.stub', $folder . '/.gitignore');
        copy(__DIR__ . '/../resources/stubs/includes/_errors.md', $folder . '/includes/_errors.md');
        copy(__DIR__ . '/../resources/stubs/package.json', $folder . '/package.json');
        copy(__DIR__ . '/../resources/stubs/gulpfile.js', $folder . '/gulpfile.js');
        copy(__DIR__ . '/../resources/stubs/config.php', $folder . '/config.php');
        copy(__DIR__ . '/../resources/stubs/js/all.js', $folder . '/../js/all.js');
        copy(__DIR__ . '/../resources/stubs/css/style.css', $folder . '/../css/style.css');

        // copy resources
        rcopy(__DIR__ . '/../resources/images/', $folder . '/assets/images');
        rcopy(__DIR__ . '/../resources/js/', $folder . '/assets/js');
        rcopy(__DIR__ . '/../resources/stylus/', $folder . '/assets/stylus');
    }

    /**
     * Generate the API documentation using the markdown and include files
     *
     * @param $folder
     * @return false|null
     */
    public function generate($folder)
    {
        $source_dir = $folder . '/source';

        if (!is_dir($source_dir)) {
            return false;
        }

        $parser = new Parser();

        $document = $parser->parse(file_get_contents($source_dir . '/index.md'));

        $frontmatter = $document->getYAML();
        $html = $document->getContent();
        $renderer = new BladeRenderer([
            'paths'=>[__DIR__ . '/../resources/views'],
            'cache_path' => $source_dir . '/_tmp'
        ]);

        // Parse and include optional include markdown files
        if (isset($frontmatter['includes'])) {
            foreach ($frontmatter['includes'] as $include) {
                if (file_exists($include_file = $source_dir . '/includes/_' . $include . '.md')) {
                    $document = $parser->parse(file_get_contents($include_file));
                    $html .= $document->getContent();
                }
            }
        }

        $output = $renderer->render('index', [
            'page' => $frontmatter,
            'content' => $html
        ]);

        file_put_contents($folder . '/index.html', $output);

        // Copy assets
        rcopy($source_dir . '/assets/images/', $folder . '/images');
        rcopy($source_dir . '/assets/stylus/fonts/', $folder . '/css/fonts');
    }

}
