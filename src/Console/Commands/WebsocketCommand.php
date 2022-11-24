<?php

namespace Sirj3x\Websocket\Console\Commands;

use App\Websocket\Server;
use Illuminate\Console\Command;
use Workerman\Worker;

class WebsocketCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ws:worker {action} {--d}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For run websocket and use that.';

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
        $GLOBALS["argv"] = $this->getArgv();

        // set timezone
        date_default_timezone_set(config('app.timezone'));

        // check config is existed
        if (!config('websocket.ptc_channel_ip') || !is_array(config('websocket.context'))) {
            $this->error('Can\'t find config file.');
            return;
        }

        // Create a server And Run worker
        new Server();

        new \Channel\Server(config('websocket.ptc_channel_ip'), config('websocket.ptc_channel_port'));
        //new \Channel\Server('unix:///tmp/workerman-channel.sock');

        Worker::runAll();
    }

    private function getArgv(): array
    {
        $args = [
            $this->argument('action')
        ];

        if ($this->option('d')) {
            $args[] = "-d";
        }

        return $args;
    }
}
