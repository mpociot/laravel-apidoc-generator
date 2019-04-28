<?php

namespace Mpociot\ApiDoc\Commands;

use Illuminate\Console\Command;
use Mpociot\Documentarian\Documentarian;

class RebuildDocumentation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apidoc:rebuild';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild your API documentation from your markdown file.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return false|null
     */
    public function handle()
    {
        $outputPath = config('apidoc.output');

        $documentarian = new Documentarian();

        if (! is_dir($outputPath)) {
            $this->error('There is no existing documentation available at '.$outputPath.'.');

            return false;
        }
        $this->info('Rebuilding API HTML code from '.$outputPath.'/source/index.md');

        $documentarian->generate($outputPath);

        $this->info('Wrote HTML documentation to: '.$outputPath.'/index.html');
    }
}
