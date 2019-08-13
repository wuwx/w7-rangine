<?php
/**
 * @author donknap
 * @date 18-7-19 上午10:25
 */

namespace W7;

use W7\Console\Application;
use W7\Core\Cache\Cache;
use W7\Core\Config\Config;
use W7\Core\Helper\Loader;
use W7\Core\Log\Logger;
use W7\Core\Log\LogManager;
use W7\Core\Helper\Storage\Context;
use W7\Core\Provider\ProviderManager;
use W7\Http\Server\Server;
use Whoops\Handler\PlainTextHandler;
use Whoops\Run;

class App {
	private static $self;
	/**
	 * 服务器对象
	 *
	 * @var Server
	 */
	public static $server;
	/**
	 * @var Loader
	 */
	private $loader;


	public function __construct() {
		self::$self = $this;

		//初始化配置
		iconfig();
		$this->registerRuntimeEnv();
		$this->registerSecurityDir();
		$this->registerErrorHandler();
		$this->registerProvider();
	}

	private function registerRuntimeEnv() {
		date_default_timezone_set('Asia/Shanghai');
	}

	private function registerSecurityDir() {
		//设置安全限制目录
		$openBaseDirConfig = iconfig()->getUserAppConfig('setting')['basedir'] ?? [];
		if (is_array($openBaseDirConfig)) {
			$openBaseDirConfig = implode(':', $openBaseDirConfig);
		}

		$openBaseDir = [
			'/tmp',
			sys_get_temp_dir(),
			APP_PATH,
			BASE_PATH . '/config',
			BASE_PATH . '/route',
			BASE_PATH . '/public',
			RUNTIME_PATH,
			BASE_PATH . '/vendor',
			$openBaseDirConfig,
			session_save_path()
		];
		ini_set('open_basedir', implode(':', $openBaseDir));
	}

	private function registerErrorHandler() {
		//设置了错误级别后只会收集错误级别内的日志, 容器确认后, 系统设置进行归类处理
		$setting = iconfig()->getUserAppConfig('setting');
		$errorLevel = $setting['error_reporting'] ?? ((ENV & RELEASE) === RELEASE ? E_ALL^E_NOTICE^E_WARNING : -1);
		error_reporting($errorLevel);

		/**
		 * 设置错误信息接管
		 */
		$processer = new Run();
		$handle = new PlainTextHandler($this->getLogger());
		if ((ENV & BACKTRACE) !== BACKTRACE) {
			$handle->addTraceToOutput(false);
			$handle->addPreviousToOutput(false);
		}
		$processer->prependHandler($handle);
		$processer->allowQuit(false);
		$processer->writeToOutput(false);
		$processer->register();
	}

	private function registerProvider() {
		iloader()->singleton(ProviderManager::class)->register()->boot();
	}

	public static function getApp() {
		if (!self::$self) {
			new static();
		}
		return self::$self;
	}

	public function runConsole() {
		try{
			iloader()->singleton(Application::class)->run();
		} catch (\Throwable $e) {
			ioutputer()->error($e->getMessage());
		}
	}

	public function getLoader() {
		if (empty($this->loader)) {
			$this->loader = new Loader();
		}
		return $this->loader;
	}

	/**
	 * @return Logger
	 */
	public function getLogger() {
		/**
		 * @var LogManager $logManager
		 */
		$logManager = iloader()->singleton(LogManager::class);
		return $logManager->getDefaultChannel();
	}

	/**
	 * @return Context
	 */
	public function getContext() {
		return $this->getLoader()->singleton(Context::class);
	}

	public function getConfigger() {
		return $this->getLoader()->singleton(Config::class);
	}

	/**
	 * @return Cache
	 */
	public function getCacher() {
		/**
		 * @var Cache $cache;
		 */
		$cache = $this->getLoader()->singleton(Cache::class);
		return $cache;
	}
}
