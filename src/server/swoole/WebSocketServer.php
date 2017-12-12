<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/12/9
 * Time: 上午11:49
 */

namespace tsingsun\daemon\server\swoole;

use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Swoole\Http\Request;


class WebSocketServer extends HttpServer
{
    public function onOpen(Server $server,$worker_id)
    {
        if($this->bootstrap){
            $this->bootstrap->onOpen($server,$worker_id);
        }
    }

    public function onMessage(Server $ws,Frame $frame){
        if($this->bootstrap){
            $this->bootstrap->onMessage($ws,$frame);
        }
    }

    public function onClose(Server $ws, $fd) {
        if($this->bootstrap){
            $this->bootstrap->onClose($ws,$fd);
        }
    }
}