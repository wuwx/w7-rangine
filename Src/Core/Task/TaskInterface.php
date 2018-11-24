<?php
/**
 * @author donknap
 * @date 18-7-25 下午3:04
 */

namespace W7\Core\Task;

use Swoole\Server;

interface TaskInterface {
	/**
	 * 线程具体执行内容
	 * @return mixed
	 */
	public function run(...$params);

	public function finish(Server $server, int $taskId, $data);
}