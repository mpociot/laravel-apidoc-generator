<?php

namespace Mpociot\ApiDoc\Commands;

use Illuminate\Console\Command;
use Mpociot\Documentarian\Documentarian;

class UpdateDocumentation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:update 
                            {--location=public/docs : The documentation location}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update and rebuild your API documentation from your markdown file.';

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
        $outputPath = $this->option('location');

        $documentarian = new Documentarian();

        if (! is_dir($outputPath)) {
            $this->error('There is no generated documentation available at '.$outputPath.'.');

            return false;
        }
        $this->info('Updating API HTML code');

        $documentarian->generate($outputPath);

        $this->info('Wrote HTML documentation to: '.$outputPath.'/public/index.html');
    }
}
