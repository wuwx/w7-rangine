<?php
/**
 * @author donknap
 * @date 18-7-19 下午3:58
 */

namespace W7\Core\Base;

interface CommendInterface {
	/**
	 * 启动命令
	 * @return mixed
	 */
	public function start();

	/**
	 * 停止命令
	 * @return mixed
	 */
	public function stop();

	/**
	 * 重载配置
	 * @return mixed
	 */
	public function reload();
}