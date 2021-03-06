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

namespace W7\Core\Log;

use Monolog\Logger as MonoLogger;
use W7\Core\Log\Handler\BufferHandler;
use W7\Core\Log\Handler\HandlerAbstract;
use W7\Core\Log\Handler\HandlerInterface;
use W7\Core\Log\Processor\SwooleProcessor;

class LogManager {
	private $channel = [];
	private $config;
	private $commonProcessor = [];

	public function __construct() {
		$this->config = $this->getConfig();

		if (empty($this->config['channel'])) {
			throw new \RuntimeException('Invalid log config');
		}
		//初始化全局附加的Handler, Processor, Formatter
		$this->commonProcessor = $this->initCommonProcessor();

		$this->initChannel();
	}

	public function getConfig() {
		if ($this->config) {
			return $this->config;
		}

		$config = iconfig()->getUserConfig('log');
		if (!empty($this->config['channel'])) {
			foreach ($this->config['channel'] as $name => &$setting) {
				if (!empty($setting['level'])) {
					$setting['level'] = MonoLogger::toMonologLevel($setting['level']);
				}
			}
		}
		return $config;
	}

	private function checkHandler($handler) {
		$handlerClass = sprintf('\\W7\\Core\\Log\\Handler\\%sHandler', ucfirst($handler));
		if (!class_exists($handlerClass)) {
			//用户自定义的handler
			$handlerClass = sprintf('\\W7\\App\\Handler\\Log\\%sHandler', ucfirst($handler));
		}
		if (!class_exists($handlerClass)) {
			throw new \RuntimeException('log handler ' . $handler . ' is not supported');
		}

		$reflectClass = new \ReflectionClass($handlerClass);
		if (!in_array(HandlerInterface::class, array_keys($reflectClass->getInterfaces()))) {
			throw new \RuntimeException('please implements ' . HandlerInterface::class);
		}

		return $handlerClass;
	}

	/**
	 * 初始化通道，
	 * @return bool
	 */
	private function initChannel() {
		$stack = [];
		$channelConfig = $this->config['channel'];
		//先初始化单个通道，记录下相关的Handler，再初始化复合通道
		foreach ($channelConfig as $name => $channel) {
			if (empty($channel['driver'])) {
				continue;
			}
			if ($channel['driver'] == 'stack') {
				$stack[$name] = $channel;
			} else {
				$this->addChannel($name, $channel['driver'], $channel);
			}
		}

		if (!empty($stack)) {
			foreach ($stack as $name => $setting) {
				$logger = $this->getLogger($name);

				if (is_array($setting['channel'])) {
					foreach ($setting['channel'] as $channel) {
						if (!empty($this->channel[$channel]) && !is_null($this->channel[$channel]['handler'])) {
							$logger->pushHandler($this->channel[$channel]['handler']);
						}
					}
				} else {
					if (!is_null($this->channel[$channel]['handler'])) {
						$logger->pushHandler($this->channel[$setting['channel']]['handler']);
					}
				}

				$this->channel[$name]['logger'] = $logger;
			}
		}

		return true;
	}

	private function initCommonProcessor() {
		$swooleProcessor = iloader()->get(SwooleProcessor::class);
		//不记录产生日志的文件和行号
		//异常中会带，普通日志函数又是一样的
		//$introProcessor = iloader()->singleton(IntrospectionProcessor::class);
		return [
			//用户自定义processor?
			$swooleProcessor
		];
	}

	public function addChannel($name, $driver, $options = []) {
		$this->config['channel'][$name] = array_merge($options, ['driver' => $driver]);
		/**
		 * @var HandlerAbstract $handlerClass
		 */
		$handlerClass = $this->checkHandler($driver);

		$bufferLimit = $options['buffer_limit'] ?? 1;
		$handler = new BufferHandler($handlerClass::getHandler($options), $bufferLimit, $options['level'], true, true);

		$logger = null;
		if (!is_null($handler)) {
			$logger = $this->getLogger($name);
			$logger->pushHandler($handler);
		}

		$this->channel[$name]['handler'] = $handler;
		$this->channel[$name]['logger'] = $logger;
	}

	public function getDefaultChannel() {
		if (empty($this->config['default'])) {
			throw new \RuntimeException('It is not set default logger');
		}
		return $this->getChannel($this->config['default']);
	}

	public function getChannel($name) {
		if (isset($this->channel[$name]) && $this->channel[$name]['logger'] instanceof MonoLogger) {
			return $this->channel[$name]['logger'];
		} else {
			//不存在指定的日志通道时，返回默认
			return $this->getDefaultChannel();
		}
	}

	private function getLogger($name) {
		$logger = new Logger($name, [], []);
		$logger->bufferLimit = $this->config['channel'][$name]['buffer_limit'] ?? 1;

		foreach ($this->commonProcessor as $processor) {
			$logger->pushProcessor($processor);
		}
		return $logger;
	}

	public function getLoggers($channel = null) {
		if ($channel) {
			return [$this->channel[$channel]['logger']];
		}

		return array_column($this->channel, 'logger');
	}

	public function cleanLogFile() {
		if ((ENV & CLEAR_LOG) !== CLEAR_LOG) {
			return false;
		}
		$logPath = RUNTIME_PATH . DS. 'logs/*';
		$tree = glob($logPath);
		if (!empty($tree)) {
			foreach ($tree as $file) {
				if (strstr($file, '.log') !== false) {
					unlink($file);
				}
			}
		}
		return true;
	}
}
