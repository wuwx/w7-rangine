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

namespace W7\Core\Session\Handler;

class CacheHandler extends HandlerAbstract {
	private function getCache() {
		return icache()->channel($this->config['cache_channel'] ?? 'default');
	}

	public function destroy($session_id) {
		return $this->getCache()->delete($session_id);
	}

	public function write($session_id, $session_data) {
		return $this->getCache()->set($session_id, $session_data, $this->getExpires());
	}

	public function read($session_id) {
		return $this->getCache()->get($session_id);
	}

	public function gc($maxlifetime) {
		return true;
	}
}
