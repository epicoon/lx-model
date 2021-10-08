<?php

namespace lx\model\plugins\modelManager;

use lx;

class Plugin extends \lx\Plugin {
	public function getSupportedServiceName() {
		return $this->attributes->service;
	}

	public function getSupportedService() {
		return lx::$app->getService($this->getSupportedServiceName());
	}

}
