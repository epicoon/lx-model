<?php

namespace lx\model\plugins\modelManager\backend;

class MigrationsBack extends \lx\Respondent {
    private $mock = true;

	/**
	 *
	 * */
	public function getData() {
	    if ($this->mock) {
	        return [];
        }
	    
		$service = $this->getSupportedService();
		if ( ! $service) {
			return [];
		}

		$migrationMap = new ServiceMigrationMap($service);
		return $migrationMap->getDetailedList();
	}

	/**
	 *
	 * */
	public function upMigration($migrationName) {
        if ($this->mock) {
            return true;
        }

		$service = $this->getSupportedService();
		if ( ! $service) {
			return false;
		}

		$migrationManager = new MigrationManager();
		return $migrationManager->upMigration($service, $migrationName);
	}

	/**
	 *
	 * */
	public function downMigration($migrationName) {
        if ($this->mock) {
            return true;
        }

		$service = $this->getSupportedService();
		if ( ! $service) {
			return false;
		}

		$migrationManager = new MigrationManager();
		return $migrationManager->downMigration($service, $migrationName);
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 *
	 * */
	private function getSupportedService() {
		return $this->plugin->getSupportedService();
	}
}
