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

namespace W7\WebSocket\Server;

use Swoole\WebSocket\Server as WebSocketServer;
use W7\Core\Server\ServerAbstract;
use W7\Core\Config\Event;
use W7\WebSocket\Message\Message;
use W7\WebSocket\Parser\JsonParser;

class Server extends ServerAbstract {
	public $type = parent::TYPE_WEBSOCKET;

	public function start() {
		$this->server = $this->getServer();
		$this->server->set($this->setting);

		ievent(Event::ON_USER_BEFORE_START, [$this->server]);
		//执行一些公共操作，注册事件等
		$this->registerService();

		$this->server->start();
	}

	/**
	 * @var \Swoole\Server $server
	 * 通过侦听端口的方法创建服务
	 */
	public function listener($server) {
		$tcpServer = $server->addListener($this->connection['host'], $this->connection['port'], $this->connection['sock_type']);
		//tcp需要强制关闭其它协议支持，否则继续父服务
		$tcpServer->set([
			'open_http2_protocol' => false,
			'open_http_protocol' => false
		]);
		$event = \iconfig()->getEvent()[parent::TYPE_WEBSOCKET];
		foreach ($event as $eventName => $class) {
			if (empty($class)) {
				continue;
			}
			$object = \iloader()->singleton($class);
			$tcpServer->on($eventName, [$object, 'run']);
		}
	}

	public function getServer() {
		if (empty($this->server)) {
			$this->server = new WebSocketServer($this->connection['host'], $this->connection['port'], $this->connection['mode'], $this->connection['sock_type']);
		}
		return $this->server;
	}

	public function sendTo($fd, Message $message) {
		if (!$this->server->isEstablished($fd)) {
			return false;
		}
		//parse 待定
		$this->server->push($fd, (new JsonParser())->encode($message));
	}

	public function sendToSome(array $fds, Message $message) {
		foreach ($fds as $fd) {
			$this->sendTo($fd, $message);
		}
	}

	public function sendToAll(Message $message) {
		$this->pageEach(function ($fd) use ($message) {
			$this->sendTo($fd, $message);
		});
	}

	/**
	 * Pagination traverse all valid WS connection
	 *
	 * @param callable $handler
	 * @param int      $pageSize
	 *
	 * @return int
	 */
	public function pageEach(callable $handler, int $pageSize = 50): int {
		$count = $startFd = 0;

		while (true) {
			$fdList = (array)$this->server->getClientList($startFd, $pageSize);
			if (($num = count($fdList)) === 0) {
				break;
			}

			$count += $num;

			/** @var $fdList array */
			foreach ($fdList as $fd) {
				$handler($fd);
			}

			// It's last page.
			if ($num < $pageSize) {
				break;
			}

			// Get start fd for next page.
			$startFd = end($fdList);
		}

		return $count;
	}

	public function disconnect(int $fd, int $code = 0, string $reason = ''): bool {
		if ($this->server->isEstablished($fd)) {
			return $this->server->disconnect($fd, $code, $reason);
		}

		return true;
	}
}
