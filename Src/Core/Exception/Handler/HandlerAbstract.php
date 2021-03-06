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

namespace W7\Core\Exception\Handler;

use W7\Core\Exception\FatalExceptionAbstract;
use W7\Core\Helper\StringHelper;
use W7\Http\Message\Server\Response;

abstract class HandlerAbstract {
	private $serverType;

	private $response;

	public function setServerType($serverType): void {
		$this->serverType = $serverType;
	}

	protected function getServerType() {
		return $this->serverType;
	}

	public function setResponse(Response $response): void {
		$this->response = $response;
	}

	/**
	 * @return Response
	 */
	public function getResponse() {
		return $this->response;
	}

	protected function log(\Throwable $throwable) {
		if ($throwable instanceof FatalExceptionAbstract) {
			$throwable = $throwable->getPrevious();
		}

		$errorMessage = sprintf(
			'Uncaught Exception %s: "%s" at %s line %s',
			get_class($throwable),
			$throwable->getMessage(),
			$throwable->getFile(),
			$throwable->getLine()
		);

		$context = [];
		if ((ENV & BACKTRACE) === BACKTRACE) {
			$context = array('exception' => $throwable);
		}

		ilogger()->debug($errorMessage, $context);
	}

	/**
	 * 此函数用于接管代码中抛出的异常，根据情况来做处理
	 * 业务层也可替换此类
	 * @param \Throwable $e
	 * @return Response
	 */
	public function handle(\Throwable $e) : Response {
		if ((ENV & DEBUG) === DEBUG) {
			return $this->handleDevelopment($e);
		} else {
			return $this->handleRelease($e);
		}
	}

	protected function getServerFatalExceptionClass() {
		return sprintf('W7\\%s\\Exception\\FatalException', StringHelper::studly($this->getServerType()));
	}

	/**
	 * 用于处理正式环境的错误返回
	 * @param \Throwable $e
	 * @return Response
	 */
	abstract protected function handleDevelopment(\Throwable $e) : Response;

	/**
	 * 用于处理开发环境的错误返回
	 * @param \Throwable $e
	 * @return Response
	 */
	abstract protected function handleRelease(\Throwable $e) : Response;
}
