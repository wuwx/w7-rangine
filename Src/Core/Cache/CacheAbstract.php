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

namespace W7\Core\Cache;

use Psr\SimpleCache\CacheInterface;

abstract class CacheAbstract implements CacheInterface {
	/**
	 * @var ConnectorManager
	 */
	protected $manager;
	protected $channelName = 'default';

	/**
	 * 选择一个缓存通道
	 * @param $name
	 * @return $this
	 */
	public function channel($name) {
		if (empty($name)) {
			throw new \RuntimeException('Invalid cache channel name');
		}
		if (!iloader()->has('cache-' . $name)) {
			throw new \RuntimeException('cache not support the channel');
		}
		return iloader()->get('cache-' . $name);
	}

	protected function getConnection() {
		$this->manager = iloader()->get(ConnectorManager::class);
		return $this->manager->connect($this->channelName);
	}

	public function setChannelName(string $channelName) {
		$this->channelName = $channelName;
	}
}
