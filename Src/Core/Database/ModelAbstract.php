<?php
/**
 * @author donknap
 * @date 18-7-30 下午3:30
 */

namespace W7\Core\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use W7\App;
use W7\Core\Database\Pool\MasterPool;
use W7\Core\Process\MysqlPoolprocess;

class ModelAbstract extends Model
{
	protected function insertAndSetId(Builder $query, $attributes) {
		$id = $query->insertGetId($attributes, $keyName = $this->getKeyName());

		$this->setAttribute($keyName, $id);
	}
}
