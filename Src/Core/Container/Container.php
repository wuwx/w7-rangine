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

namespace W7\Core\Container;

use Pimple\Container as PimpleContainer;
use Pimple\Psr11\Container as PsrContainer;

/**
 * ＠@mixin PimpleContainer
 */
class Container {
	private $container;
	private $psrContainer;

	public function __construct() {
		$this->container = new PimpleContainer();
		$this->psrContainer = new PsrContainer($this->container);
	}

	/**
	 * @param $name
	 * @param $handle
	 * @param mixed ...$params
	 * @return bool
	 */
	public function set($name, $handle, ...$params) {
		if (is_string($handle)) {
			$handle = function () use ($handle, $params) {
				return new $handle(...$params);
			};
		}
		$this->container[$name] = $handle;
	}

	public function get($name) {
		if (!$this->has($name)) {
			$this->set($name, $name);
		}

		return $this->psrContainer->get($name);
	}
	
	public function has($name) {
		return $this->psrContainer->has($name);
	}

	/**
	 * @deprecated
	 * @param $name
	 * @return mixed
	 */
	public function singleton($name) {
		return $this->get($name);
	}

	public function __call($name, $arguments) {
		return $this->container->$name(...$arguments);
	}
}