<?php

namespace Sirj3x\Websocket\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;

class WebsocketSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ws:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup websocket and generate setup files';

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
     */
    public function handle()
    {
        $this->makeServerClass();

        $this->info('The package was launched successfully.');
    }

    private function makeServerClass()
    {
        $stub = $this->laravel->basePath('packages' . DIRECTORY_SEPARATOR . 'sirj3x' . DIRECTORY_SEPARATOR . 'websocket' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'setup.stub');
        $content = File::get($stub);

        $targetFileDir = app_path('Websocket');
        $targetFileName = 'Server.php';
        $targetFile = $targetFileDir . DIRECTORY_SEPARATOR . $targetFileName;

        $fileDirectory = new Filesystem();
        if (!$fileDirectory->isDirectory($targetFileDir)) {
            $fileDirectory->makeDirectory($targetFileDir);
        }

        if (!file_exists($targetFile)) {
            $fileDirectory->put($targetFile, $content);
        }
    }
}
