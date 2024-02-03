<?php

namespace Sirj3x\Websocket\Console\Commands;

use App\Websocket\Server;
use Illuminate\Console\Command;
use Sirj3x\Websocket\Helpers\StringHelper;
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

        // create websocket
        $websocketServer = new Server();

        // create channel
        new \Channel\Server(config('websocket.ptc_channel_ip'), config('websocket.ptc_channel_port'));
        //new \Channel\Server('unix:///tmp/workerman-channel.sock');

        // create tcp-server
        $tcpServer = new Worker("tcp://" . config('websocket.ptc_tcp_ip') . ":" . config('websocket.ptc_tcp_port'));
        $tcpServer->name = 'TCPServer';
        $tcpServer->onMessage = function ($connection, $data) use ($websocketServer) {
            $data = StringHelper::parseTcpConnectionData($data);
            if ($data) $websocketServer->handlePtcData($data);
            $connection->close();
        };
        $tcpServer->listen();

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
