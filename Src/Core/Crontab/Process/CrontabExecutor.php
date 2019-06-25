<?php

namespace W7\Core\Crontab\Process;

use W7\Core\Dispatcher\TaskDispatcher;
use W7\Core\Process\ProcessAbstract;

class CrontabExecutor extends ProcessAbstract {
	public function run() {
		while($data = $this->process->pop()){
			if ($data) {
				/**
				 * @var TaskDispatcher $taskDispatcher
				 */
				ilogger()->info('pop crontab task ' .$data . ' at ' . $this->process->pid);
				$taskDispatcher = iloader()->singleton(TaskDispatcher::class);
				$result = $taskDispatcher->dispatch($this->process, -1 , $this->process->pid, $data);
				if ($result === false) {
					continue;
				}
				ilogger()->info('complete crontab task ' . $result->task . ' with data ' .$data . ' at ' . $this->process->pid . ' with $result ' . $result->error);
			}
		}
	}

	public function stop() {
		ilogger()->info('crontab executor process exit');
	}
}