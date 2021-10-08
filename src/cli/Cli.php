<?php

namespace lx\model\cli;

use lx\CliProcessor;
use lx\CliArgument;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ObjectTrait;
use lx\ServiceCliInterface;
use lx\ServiceCliExecutor;

class Cli implements FusionComponentInterface, ServiceCliInterface
{
	use FusionComponentTrait;

	public function getCliCommandsConfig(): array
	{
		return [
			[
				'command' => 'model-status',
                'description' => 'Show models status',
                'arguments' => [
                    ServiceCliExecutor::getServiceArgument(),
                    (new CliArgument())->setKeys(['model', 'm'])
                        ->setDescription('Model name or array of names'),
                ],
				'handler' => ModelStatus::class,
			],

            [
                'command' => 'model-update',
                'description' => 'Update models: synchronizing with mediators, generating migrations, applying migrations',
                'arguments' => [
                    ServiceCliExecutor::getServiceArgument(),
                    (new CliArgument())->setKeys(['model', 'm'])
                        ->setDescription('Model name or array of names'),
                    (new CliArgument())->setKeys(['level', 'l'])
                        ->setEnum([
                            ModelUpdate::LEVEL_FULL,
                            ModelUpdate::LEVEL_MEDIATOR,
                            ModelUpdate::LEVEL_GEN_MIGRATION,
                            ModelUpdate::LEVEL_RUN_MIGRATION,
                        ])
                        ->setDescription(''),
                ],
                'handler' => ModelUpdate::class,
            ],

            [
                'command' => 'model-create-migration',
                'description' => 'Create new migration. You have to choose a service',
                'arguments' => [
                    ServiceCliExecutor::getServiceArgument(),
                ],
                'handler' => CreateMigration::class,
            ],

            [
                'type' => CliProcessor::COMMAND_TYPE_WEB,
                'command' => 'model-migrations-manage',
                'description' => 'Run plugin to manage model statuses and migrations',
                'handler' => MigrationsManage::class,
            ],
        ];
	}
}
