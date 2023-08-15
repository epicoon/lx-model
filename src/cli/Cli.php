<?php

namespace lx\model\cli;

use lx\CliProcessor;
use lx\CommandArgument;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ServiceCliInterface;

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
                    CommandArgument::service(),
                    (new CommandArgument())->setKeys(['model', 'm'])
                        ->setDescription('Model name or array of names'),
                ],
				'handler' => ModelStatus::class,
			],

            [
                'command' => 'model-update',
                'description' => 'Update models: synchronizing with mediators, generating migrations, applying migrations',
                'arguments' => [
                    CommandArgument::service(),
                    (new CommandArgument())->setKeys(['model', 'm'])
                        ->setDescription('Model name or array of names'),
                    (new CommandArgument())->setKeys(['level', 'l'])
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
                    CommandArgument::service(),
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
