<?php

namespace lx\model\cli;

use lx\model\repository\MigrationReporter;
use lx\ServiceCliExecutor;

class ModelStatus extends ServiceCliExecutor
{
    public function run(): void
    {
        $this->defineService();
        $models = $this->processor->getArg('model');
        if ($models) {
            if (!$this->service) {
                $this->processor->outln('Models are belonged to services. You have to point a service.');
                return;
            }

            $models = (array)$models;
        }

        if ($this->service) {
            $this->runForService($models);
            return;
        }

        $this->runForAll();
    }

    private function runForAll(): void
    {
        $fullReport = MigrationReporter::getServicesData();

        foreach ($fullReport as $item) {
            $report = $item['report'];
            if (empty($report['unappliedMigrations'])
                && empty($report['modelsNeedTable'])
                && empty($report['modelsChanged'])
                && empty($report['modelsNeedUpdate'])
            ) {
                continue;
            }

            $this->processor->outln(
                '*** Service: ' . $item['serviceName'] . ', category: ' . $item['serviceCategory'],
                ['decor' => 'b']
            );
            $this->printServiceReport($report);
            $this->processor->outln('***', ['decor' => 'b']);
        }
    }

    private function runForService(?array $models): void
    {
        $report = MigrationReporter::getServiceData($this->service->name, $models);
        $this->printServiceReport($report['report'] ?? []);
    }

    private function printServiceReport(array $report): void
    {
        if (empty($report)) {
            $this->processor->outln('No changes');
            return;
        }

        if (!empty($report['wrongModelNames'])) {
            $this->processor->outln('* The following models not found:', ['decor' => 'b']);
            foreach ($report['wrongModelNames'] as $name) {
                $this->processor->outln('>>> ' . $name);
            }
        }

        if (!empty($report['unappliedMigrations'])) {
            $this->processor->outln('* There are unapplied migrations in this service:', ['decor' => 'b']);
            foreach ($report['unappliedMigrations'] as $name) {
                $this->processor->outln('>>> ' . $name);
            }
            return;
        }

        if (!empty($report['modelsNeedUpdate'])) {
            $this->processor->outln('* The following model schemas have changes:', ['decor' => 'b']);
            foreach ($report['modelsNeedUpdate'] as $name) {
                $this->processor->outln('>>> ' . $name);
            }
        }

        $repoNeedUpdate = array_merge($report['modelsNeedTable'], array_keys($report['modelsChanged']));
        if (!empty($repoNeedUpdate)) {
            $this->processor->outln('* The following models need repository updating:', ['decor' => 'b']);
            foreach ($repoNeedUpdate as $name) {
                $this->processor->outln('>>> ' . $name);
            }
        }
    }
}
