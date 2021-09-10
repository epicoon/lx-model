<?php

namespace lx\model\plugins\modelManager;

class Plugin extends \lx\Plugin {
	public function getSupportedServiceName() {
		return $this->attributes->service;
	}

	public function getSupportedService() {
		return $this->app->getService($this->getSupportedServiceName());
	}

}
