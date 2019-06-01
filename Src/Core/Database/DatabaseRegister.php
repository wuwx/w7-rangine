<?php

namespace W7\Core\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Fluent;
use W7\App;
use W7\Core\Database\Connection\PdoMysqlConnection;
use W7\Core\Database\Connection\SwooleMySqlConnection;
use W7\Core\Service\ServiceAbstract;

class DatabaseRegister extends ServiceAbstract {
	public function register() {
		// TODO: Implement register() method.
		$this->registerDb();
	}

	private function registerDb() {
		//新增swoole连接mysql的方式
		Connection::resolverFor('swoolemysql', function ($connection, $database, $prefix, $config) {
			return new SwooleMySqlConnection($connection, $database, $prefix, $config);
		});
		Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
			return new PdoMysqlConnection($connection, $database, $prefix, $config);
		});

		//新增swoole连接Mysql的容器
		$container = new Container();
		$container->instance('db.connector.swoolemysql', new ConnectorManager());
		$container->instance('db.connector.mysql', new ConnectorManager());

		//侦听sql执行完后的事件，回收$connection
		$dbDispatch = new Dispatcher($container);
		$dbDispatch->listen(QueryExecuted::class, function ($data) use ($container) {
			/**
			 *检测是否是事物里面的query
			 */
			if (App::getApp()->getContext()->getContextDataByKey('db-transaction')) {
				return false;
			}
			return $this->releaseDb($data, $container);
		});
		$dbDispatch->listen(TransactionBeginning::class, function ($data) {
			$connection = $data->connection;
			App::getApp()->getContext()->setContextDataByKey('db-transaction', $connection);
		});
		$dbDispatch->listen(TransactionCommitted::class, function ($data) use ($container) {
			App::getApp()->getContext()->setContextDataByKey('db-transaction', null);
			return $this->releaseDb($data, $container);
		});
		$dbDispatch->listen(TransactionRolledBack::class, function ($data) use ($container) {
			App::getApp()->getContext()->setContextDataByKey('db-transaction', null);
			return $this->releaseDb($data, $container);
		});

		$container->instance('events', $dbDispatch);

		//添加配置信息到容器
		$dbconfig = \iconfig()->getUserAppConfig('database');

		$container->instance('config', new Fluent());
		$container['config']['database.default'] = 'default';
		$container['config']['database.connections'] = $dbconfig;
		$factory = new ConnectionFactory($container);
		$dbManager = new DatabaseManager($container, $factory);

		Model::setConnectionResolver($dbManager);
	}

	private function releaseDb($data, $container) {
		$connection = $data->connection;
		ilogger()->channel('database')->debug($data->sql ?? '' . ', params: ' . implode(',', $data->bindings ?? []));

		$poolName = $connection->getPoolName();
		if (empty($poolName)) {
			return true;
		}
		list($poolType, $poolName) = explode(':', $poolName);
		if (empty($poolType)) {
			$poolType = 'swoolemysql';
		}

		$activePdo = $connection->getActiveConnection();
		if (empty($activePdo)) {
			return false;
		}
		$connectorManager = $container->make('db.connector.' . $poolType);
		$connectorManager->release($activePdo);
	}
}