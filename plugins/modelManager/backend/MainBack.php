<?php

namespace lx\model\plugins\modelManager\backend;

use lx\Respondent;
use lx\File;

class MainBack extends Respondent {
    private $mock = true;

	/**
	 *
	 * */
	public function getModelsData() {
	    if ($this->mock) {
	        return json_decode('[{"service":"lx\/auth","path":"\/home\/lx\/webprj\/lxloc\/vendor\/lx\/auth\/model\/AccessToken.yaml","code":"AccessToken:\n  fields:\n    token: {type: string}\n    user_login: {type: string}\n    expire: {type: timestamp}\n","schema":{"fields":{"token":{"type":"string","name":"token"},"user_login":{"type":"string","name":"user_login"},"expire":{"type":"timestamp","name":"expire"}}},"needTable":false,"changed":false,"needMigrate":false,"modelName":"AccessToken"},{"service":"lx\/auth","path":"\/home\/lx\/webprj\/lxloc\/vendor\/lx\/auth\/model\/AuthDefaultList.yaml","code":"AuthDefaultList:\n  fields:\n    type: {type: string}\n    id_item: {type: int}\n","schema":{"fields":{"type":{"type":"string","name":"type"},"id_item":{"type":"int","name":"id_item"}}},"needTable":false,"changed":false,"needMigrate":false,"modelName":"AuthDefaultList"},{"service":"lx\/auth","path":"\/home\/lx\/webprj\/lxloc\/vendor\/lx\/auth\/model\/AuthRight.yaml","code":"AuthRight:\n  fields:\n    name: {type: string}\n\n  relations:\n    roles: \'><AuthRole\'\n","schema":{"fields":{"name":{"type":"string","name":"name"}},"relations":{"roles":"><AuthRole"}},"needTable":false,"changed":false,"needMigrate":false,"modelName":"AuthRight"},{"service":"lx\/auth","path":"\/home\/lx\/webprj\/lxloc\/vendor\/lx\/auth\/model\/AuthRole.yaml","code":"AuthRole:\n  fields:\n    name: {type: string}\n\n  relations:\n    rights: \'><AuthRight\'\n    userRoles: \'><AuthUserRole\'\n","schema":{"fields":{"name":{"type":"string","name":"name"}},"relations":{"rights":"><AuthRight","userRoles":"><AuthUserRole"}},"needTable":false,"changed":false,"needMigrate":false,"modelName":"AuthRole"},{"service":"lx\/auth","path":"\/home\/lx\/webprj\/lxloc\/vendor\/lx\/auth\/model\/AuthUserRole.yaml","code":"AuthUserRole:\n  fields:\n    user_auth_data: {type: string}\n\n  relations:\n    roles: \'><AuthRole\'\n","schema":{"fields":{"user_auth_data":{"type":"string","name":"user_auth_data"}},"relations":{"roles":"><AuthRole"}},"needTable":false,"changed":false,"needMigrate":false,"modelName":"AuthUserRole"},{"service":"lx\/auth","path":"\/home\/lx\/webprj\/lxloc\/vendor\/lx\/auth\/model\/RefreshToken.yaml","code":"RefreshToken:\n  fields:\n    token: {type: string}\n    user_login: {type: string}\n    expire: {type: timestamp}\n","schema":{"fields":{"token":{"type":"string","name":"token"},"user_login":{"type":"string","name":"user_login"},"expire":{"type":"timestamp","name":"expire"}}},"needTable":false,"changed":false,"needMigrate":false,"modelName":"RefreshToken"}]', true);
        }

		$service = $this->getSupportedService();
		if ( ! $service) {
			return [];
		}

		$list = ModelBrowser::getList($service);

		$result = [];
		foreach ($list as $item) {
			$result[] = $this->serializeModelInfo( $item->getFullInfo() );
		};

		return $result;
	}

	/**
	 *
	 * */
	public function createModel($modelName) {
        if ($this->mock) {
            return false;
        }

		$service = $this->getSupportedService();
		if ( ! $service) {
			return false;
		}

		$provider = $service->modelProvider;
		$provider->createModel($modelName);

		return true;
	}

	/**
	 *
	 * */
	public function removeModel($modelName) {
        if ($this->mock) {
            return false;
        }

		$service = $this->getSupportedService();
		if ( ! $service) {
			return false;
		}

		$provider = $service->modelProvider;

		if ($provider->deleteModel($modelName)) {
			(new MigrationMaker($service))->deleteTableMigration($modelName);
			return true;
		}

		return false;
	}

	/**
	 *
	 * */
	public function getModelEntities($serviceName, $modelName) {
	    if ($this->mock) {
	        switch ($modelName) {
                case 'AccessToken':
                    return json_decode('{"schema":{"token":{"type":"string","notNull":false,"size":255},"user_login":{"type":"string","notNull":false,"size":255},"expire":{"type":"timestamp","notNull":false},"id":{"type":"pk"}},"entities":[{"id":1,"token":"22beccb2de03f1c75630db3e0009dc76","user_login":"1","expire":"2021-01-30 02:08:46"},{"id":2,"token":"2aeeadf242bdd245f69d7f29863b3e6a","user_login":"2","expire":"2021-01-30 02:09:42"}]}', true);
                case 'AuthDefaultList':
                    return json_decode('{"schema":{"type":{"type":"string","notNull":false,"size":255},"id_item":{"type":"string","notNull":false,"size":255},"id":{"type":"pk"}},"entities":[{"id":1,"type":"new-user-role","id_item":"1"}]}', true);
                case 'AuthRight':
                    return json_decode('{"schema":{"name":{"type":"string","notNull":false,"size":255},"id":{"type":"pk"}},"entities":[{"id":1,"name":"client_w"},{"id":2,"name":"client_r"},{"id":3,"name":"admin_w"},{"id":4,"name":"admin_r"},{"id":5,"name":"superadmin_w"},{"id":6,"name":"superadmin_r"}]}', true);
                case 'AuthRole':
                    return json_decode('{"schema":{"name":{"type":"string","notNull":false,"size":255},"id":{"type":"pk"}},"entities":[{"id":1,"name":"client"},{"id":2,"name":"admin"},{"id":3,"name":"superadmin"}]}', true);
                case 'AuthUserRole':
                    return json_decode('{"schema":{"user_auth_data":{"type":"string","notNull":false,"size":255},"id":{"type":"pk"}},"entities":[{"id":1,"user_auth_data":"1"},{"id":2,"user_auth_data":null},{"id":3,"user_auth_data":null},{"id":4,"user_auth_data":null},{"id":5,"user_auth_data":null},{"id":6,"user_auth_data":"2"},{"id":7,"user_auth_data":null},{"id":8,"user_auth_data":null},{"id":9,"user_auth_data":null},{"id":10,"user_auth_data":null},{"id":11,"user_auth_data":null},{"id":12,"user_auth_data":null},{"id":13,"user_auth_data":null},{"id":14,"user_auth_data":null},{"id":15,"user_auth_data":null},{"id":16,"user_auth_data":null},{"id":17,"user_auth_data":null},{"id":18,"user_auth_data":null}]}', true);
                case 'RefreshToken':
                    return json_decode('{"schema":{"token":{"type":"string","notNull":false,"size":255},"user_login":{"type":"string","notNull":false,"size":255},"expire":{"type":"timestamp","notNull":false},"id":{"type":"pk"}},"entities":[{"id":1,"token":"532da503ba46c995b42efcf0f135aa4a","user_login":"1","expire":"2021-01-31 01:33:46"},{"id":2,"token":"15f6c15fbcdc0303812afc5fb48f2400","user_login":"2","expire":"2021-01-31 01:34:42"}]}', true);
            }
            return '';
        }


		$service = $this->app->getService($serviceName);
		if ( ! $service) {
			return [];
		}

		$data = [];
		$manager = $service->modelProvider->getManager($modelName);
		$models = $manager->loadModels();
		foreach ($models as $model) {
			$data[] = $model->getFields();
		}

		$mb = new ModelBrowser(['service' => $service, 'modelName' => $modelName]);
		$entitySchema = $mb->getEntitySchema(
			['pk', 'type', 'default', 'notNull', 'size'],
			ModelSchema::MODE_SAVED_FIELDS
		);

		return [
			'schema' => $entitySchema,
			'entities' => $data,
		];
	}

	/**
	 * Проверить и накатить все изменения для модели, которые есть на серверной стороне
	 * */
	public function migrate($modelName) {
        if ($this->mock) {
            return [
                'success' => false,
                'data' => 'Mock mode',
            ];
        }

		$service = $this->getSupportedService();
		if ( ! $service) {
			return [
				'success' => false,
				'data' => 'service is not defined',
			];
		}

		$migrationManager = new MigrationManager();
		try {
			$result = $migrationManager->runModel($service, $modelName);
			return [
				'success' => true,
				'data' => $result,
			];
		} catch (\Exception $e) {
			return [
				'success' => false,
				'data' => $e->getMessage(),
			];
		}
	}

	/**
	 *
	 * */
	public function correctModel($service, $modelName, $actions) {
        if ($this->mock) {
            return [
                'success' => false,
                'data' => 'Mock mode',
            ];
        }

		try {
			$migrationManager = new ModelMigrationManager([
				'service' => $this->app->getService($service),
				'modelName' => $modelName,
			]);
			$migrationManager->runCorrectActions($actions);
		} catch (\Exception $e) {
			return [
				'success' => false,
				'data' => $e->getMessage(),
			];
		}

		$data = new ModelBrowser([
			'service' => $service,
			'modelName' => $modelName,
		]);

		return [
			'success' => true,
			'data' => $this->serializeModelInfo($data->getFullInfo()),
		];
	}

	/**
	 *
	 * */
	public function correctModelByCode($service, $modelName, $path, $code) {
        if ($this->mock) {
            return [
                'success' => false,
                'data' => 'Mock mode',
            ];
        }

		try {
			$file = new File($path);
			$file->put($code);
			$migrationManager = new ModelMigrationManager([
				'service' => $this->app->getService($service),
				'modelName' => $modelName,
				'path' => $path,
				'code' => $code,
			]);
			$migrationManager->run();
		} catch (\Exception $e) {
			return [
				'success' => false,
				'data' => $e->getMessage(),
			];
		}

		$data = new ModelBrowser([
			'service' => $service,
			'modelName' => $modelName,
			'path' => $path,
		]);

		return [
			'success' => true,
			'data' => $this->serializeModelInfo($data->getFullInfo()),
		];
	}

	/**
	 *
	 * */
	public function addModelEntity($serviceName, $modelName, $data) {
        if ($this->mock) {
            return false;
        }

		$service = $this->app->getService($serviceName);
		$manager = $service->modelProvider->getManager($modelName);
		$model = $manager->newModel();
		$model->setFields($data);
		$model->save();

		return true;
	}

	/**
	 *
	 * */
	public function delModelEntities($serviceName, $modelName, $ids) {
        if ($this->mock) {
            return false;
        }

		$service = $this->app->getService($serviceName);
		$manager = $service->modelProvider->getManager($modelName);
		$models = $manager->loadModels($ids);
		$manager->deleteModels($models);

		return true;
	}

	/**
	 *
	 * */
	public function saveEntityChange($serviceName, $modelName, $entityFields) {
        if ($this->mock) {
            return false;
        }

		$service = $this->app->getService($serviceName);
		$manager = $service->modelProvider->getManager($modelName);
		$model = $manager->loadModel($entityFields['id']);
		$model->setFields($entityFields);
		$model->save();
		return true;
	}

	/**
	 *
	 * */
	public function correctEntitiesWithMigrate($modelName, $actions) {
        if ($this->mock) {
            return [
                'success' => false,
                'data' => 'Mock mode',
            ];
        }

		$service = $this->getSupportedService();

		try {
			foreach ($actions as &$action) {
				if ($action[0] != 'add') continue;
				$pkName = $service->modelProvider->getSchema($modelName)->pkName();
				$add = [];
				foreach ($action[1] as $row) {
					unset($row[$pkName]);
					$add[] = $row;
				}
				$action[1] = $add;
			}
			unset($action);

			$migrationManager = new ModelMigrationManager([
				'service' => $this->getSupportedService(),
				'modelName' => $modelName,
			]);
			if ( ! $migrationManager->runChangeEntities($actions)) {
				throw new \Exception('Error while model-provider model entities correction', 400);
			}
		} catch (\Exception $e) {
			return [
				'success' => false,
				'data' => $e->getMessage(),
			];
		}

		return [ 'success' => true ];
	}

	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	private function serializeModelInfo($info) {
		$info['modelName'] = $info['name'];
		unset($info['name']);
		$schema = $info['schema'];
		$schemaSerialized = $schema;
		$schemaSerialized['fields'] = [];
		$specialTypes = $this->getRootService()->getSpecialModelSchemaFieldTypes();
		foreach ($schema['fields'] as $name => $data) {
			if (array_key_exists($data['type'], $specialTypes)) {
				continue;
			}

			$data['name'] = $name;
			$schemaSerialized['fields'][$name] = $data;
		}
		$info['schema'] = $schemaSerialized;
		return $info;
	}

	/**
	 *
	 * */
	private function getSupportedService() {
		return $this->plugin->getSupportedService();
	}
}
