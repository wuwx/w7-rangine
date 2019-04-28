<?php

namespace W7\Console;

use Symfony\Component\Console\Application as SymfontApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use W7\Console\Io\Output;
use W7\Core\Exception\CommandException;

class Application extends SymfontApplication {
	public function __construct() {
		$version = $this->version();
		$this->registerCommands();

		parent::__construct('w7swoole', $version);
	}

	/**
	 * Gets the default input definition.
	 *
	 * @return InputDefinition An InputDefinition instance
	 */
	protected function getDefaultInputDefinition()
	{
		return new InputDefinition([
			new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

			new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
			new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
			new InputOption('--version', '-v', InputOption::VALUE_NONE, 'Display this application version'),
			new InputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output'),
			new InputOption('--no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output'),
			new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
		]);
	}

	public function run(InputInterface $input = null, OutputInterface $output = null) {
		$output = new Output();

		return parent::run($input, $output); // TODO: Change the autogenerated stub
	}

	public function doRun(InputInterface $input, OutputInterface $output) {
		$output->writeln($this->logo());
		if (true === $input->hasParameterOption(['--version', '-v','vv', 'vvv', '--verbose'], true)) {
			$output->writeln($this->getLongVersion());
			return 0;
		}
		if (true === $input->hasParameterOption(['--help', '-h'], true)) {
			if (!$this->getCommandName($input)) {
				$input = new ArgvInput(['command' => 'list']);
			}
		}

		try{
			return parent::doRun($input, $output);
		} catch (\Throwable $e) {
			$this->renderException($e, $output);
			if ($e instanceof CommandException) {
				$input = new ArrayInput(['--help' => true,'command' => $this->getCommandName($input)]);
				$this->run($input);
			}
		}
	}

	private function registerCommands() {
		$commands = glob(RANGINE_FRAMEWORK_PATH  . '/Console/Command/*/' . '*Command.php');
		$systemCommands = [];
		foreach ($commands as $key => &$item) {
			$item = str_replace(RANGINE_FRAMEWORK_PATH . '/Console/Command/', '', $item);
			$info = pathinfo($item);
			$name = strtolower(rtrim($info['dirname'] . ':' . $info['filename'], 'Command'));

			$systemCommands[$name] = "\\W7\\Console\\Command\\" . $info['dirname'] . "\\" . $info['filename'];
		}
		$systemCommands = array_merge($systemCommands, iconfig()->getServerCommand());

		$userCommands = iconfig()->getUserConfig('command');
		$commands = array_merge($systemCommands, $userCommands);

		foreach ($commands as $name => $class) {
			$commandObj = new $class($name);
			$this->add($commandObj);
		}
	}

	private function logo() {
		return "
__      _______ _______                   _      
\ \    / /  ___  / ___|_      _____   ___ | | ___ 
 \ \ /\ / /   / /\___ \ \ /\ / / _ \ / _ \| |/ _ \
  \ V  V /   / /  ___) \ V  V / (_) | (_) | |  __/
   \_/\_/   /_/  |____/ \_/\_/ \___/ \___/|_|\___|
";
	}

	private function version() {
		$frameworkVersion = \iconfig()::VERSION;
		$phpVersion = PHP_VERSION;
		$swooleVersion = SWOOLE_VERSION;
		$version = "framework: $frameworkVersion, php: $phpVersion, swoole: $swooleVersion";

		return $version;
	}
}