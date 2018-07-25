<?php
/**
 * @author donknap
 * @date 18-7-21 上午11:08
 */

namespace W7\Http\Listener;

use Swoole\Http\Request;
use Swoole\Http\Response;

class RequestListener {
	/**
	 * @param Request $request
	 * @param Response $response
	 * @return \Psr\Http\Message\ResponseInterface|Response
	 * @throws \ReflectionException
	 */
	public function onRequest(Request $request,Response $response) {
		/**
		 * @var \W7\Http\Server\Dispather $dispather
		 */
		$dispather = \iloader()->singleton(\W7\Http\Server\Dispather::class);
		$dispather->dispatch($request, $response);
	}

}