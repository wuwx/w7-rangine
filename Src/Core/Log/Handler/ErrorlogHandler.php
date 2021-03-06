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

namespace W7\Core\Log\Handler;

use Monolog\Handler\HandlerInterface as MonologInterface;

class ErrorlogHandler extends \Monolog\Handler\ErrorLogHandler implements HandlerInterface {
	public static function getHandler($config): MonologInterface {
		return new static();
	}

	public function preProcess($record) : array {
		return $record;
	}
}
