<?php

/**
 * This file is part of Rangine
 *
 * (c) We7Team 2019 <https://www.rangine.com/>
 *
 * document http://s.w7.cc/index.php?c=wiki&do=view&id=317&list=2284
 *
 * visited https://www.rangine.com/ for more details
 */

namespace W7\WebSocket\Listener;

use W7\App;
use W7\Core\Listener\ListenerAbstract;

class AfterWorkerStopListener extends ListenerAbstract {
	public function run(...$params) {
		$clientCollector = icontainer()->get('ws-client') ?? [];
		if (empty($clientCollector)) {
			return true;
		}

		foreach ($clientCollector as $fd => $client) {
			echo $fd . PHP_EOL;
			App::$server->getServer()->isEstablished($fd) && App::$server->getServer()->disconnect($fd, 0, '');
		}

		icontainer()->delete('ws-client');
	}
}
