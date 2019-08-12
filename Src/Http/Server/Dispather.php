<?php
/**
 * @author donknap
 * @date 18-7-24 下午5:31
 */

namespace W7\Http\Server;

use W7\Core\Dispatcher\RequestDispatcher;
use W7\Core\Session\Middleware\SessionMiddleware;

class Dispather extends RequestDispatcher {
	public $beforeMiddleware = [
		[SessionMiddleware::class]
	];
}
