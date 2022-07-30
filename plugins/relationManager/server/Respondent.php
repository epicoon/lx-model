<?php

namespace lx\model\plugins\relationManager\server;

use lx;
use lx\model\Model;
use lx\model\modelTools\ModelsSerializer;
use lx\model\schema\relation\ModelRelation;
use lx\HttpResponseInterface;
use lx\FlightRecorderHolderInterface;
use lx\FlightRecorderHolderTrait;

class Respondent extends \lx\Respondent implements FlightRecorderHolderInterface
{
    use FlightRecorderHolderTrait;
    
    const PER_PAGE_DEFAULT = 10;

    public function getCoreData(array $attributes): HttpResponseInterface
    {
        $serviceName = null;
        $modelName = null;
        $relation = null;

        if (array_key_exists('model', $attributes)) {
            /** @var string&Model $modelClass */
            $modelClass = Model::getModelClassName($attributes['model']);
            $serviceName = $modelClass::getModelService()->name;
            $modelName = $modelClass::getStaticModelName();
        }

        if (array_key_exists('relation', $attributes)) {
            $relation = $attributes['relation'];
        }

        return $this->prepareResponse([
            'serviceName' => $serviceName,
            'modelName' => $modelName,
            'relation' => $relation,
        ]);
    }

    public function getRelationData(
        string $serviceName,
        string $modelName,
        string $relationName,
        array $filters
    ): HttpResponseInterface
    {
        $modelClass = $this->defineModelClass($serviceName, $modelName);
        $relation = $this->defineRelation($modelClass, $relationName);
        if ($this->hasFlightRecords()) {
            return $this->prepareWarningResponse($this->getFirstFlightRecord());
        }

        /** @var string&Model $relModelClass */
        $relModelClass = $relation->getRelatedModelClassName();

        /**
         * @var Model[] $models0
         * @var int $totalCount0
         */
        list($models0, $totalCount0) = $this
            ->loadModels($modelClass, $filters[0] ?? []);
        /**
         * @var Model[] $models1
         * @var int $totalCount1
         */
        list($models1, $totalCount1) = $this
            ->loadModels($relModelClass, $filters[1] ?? []);

        $serializer = new ModelsSerializer();
        $fields0 = $modelClass::getSchemaArray()['fields'];
        //TODO PK!!!
        $fields0['id'] = ['type' => 'pk'];
        $modelsData0 = [
            'schema' => $fields0,
            'list' => $serializer->collectionToArray($models0),
        ];
        $fields1 = $relModelClass::getSchemaArray()['fields'];
        //TODO PK!!!
        $fields1['id'] = ['type' => 'pk'];
        $modelsData1 = [
            'schema' => $fields1,
            'list' => $serializer->collectionToArray($models1),
        ];

        $relationsMap = [];
        //TODO костыльно. Как еще можно реализовать получение связей?
        if ($relation->isManyToMany()) {
            $context = $modelClass::getModelRepository()->getContext();
            $nameConverter = $context->getNameConverter();
            $key0 = $nameConverter->getRelationName($modelName);
            $key1 = $nameConverter->getRelationName($relation->getRelatedModelName());
            $ids0 = [];
            foreach ($models0 as $model) {
                $ids0[] = $model->getId();
            }
            $ids1 = [];
            foreach ($models1 as $model) {
                $ids1[] = $model->getId();
            }
            $tableName = $nameConverter->getManyToManyTableName(
                $modelName,
                $relationName,
                $relation->getRelatedModelName(),
                $relation->getRelatedAttributeName()
            );
            $table = $context->getRepository()->getReplicaDb()->getTable($tableName);
            $match = $table->select('*', [
                $key0 => $ids0,
                $key1 => $ids1,
            ]);
            foreach ($match as $pare) {
                $relationsMap[] = [
                    $pare[$key0],
                    $pare[$key1]
                ];
            }
        } else {
            if ($relation->isFkHolder()) {
                foreach ($models0 as $model) {
                    $relationsMap[] = [
                        $model->getId(),
                        $model->getRelatedKey($relationName),
                    ];
                }
            } else {
                foreach ($models1 as $model) {
                    $relationsMap[] = [
                        $model->getRelatedKey($relationName),
                        $model->getId(),
                    ];
                }
            }
        }

        return $this->prepareResponse([
            'count0' => $totalCount0,
            'count1' => $totalCount1,
            'models0' => $modelsData0,
            'models1' => $modelsData1,
            'relatedServiceName' => $relModelClass::getModelService()->name,
            'relatedModelName' => $relation->getRelatedModelName(),
            'relations' => $relationsMap,
        ]);
    }

	public function createRelation(
	    string $serviceName,
        string $modelName,
        int $pk0,
        string $relationName,
        int $pk1
    ): ?HttpResponseInterface
	{
        $modelClass = $this->defineModelClass($serviceName, $modelName);
        $relation = $this->defineRelation($modelClass, $relationName);
        if ($this->hasFlightRecords()) {
            return $this->prepareWarningResponse($this->getFirstFlightRecord());
        }

        $model = $modelClass::findOne($pk0);
        $relModel = ($relation->getRelatedModelClassName())::findOne($pk1);
        $model->setRelated($relationName, $relModel);
        $model->save();

        return null;
	}

	public function deleteRelation(
        string $serviceName,
        string $modelName,
        int $pk0,
        string $relationName,
        int $pk1
    ): ?HttpResponseInterface
	{
        $modelClass = $this->defineModelClass($serviceName, $modelName);
        $relation = $this->defineRelation($modelClass, $relationName);
        if ($this->hasFlightRecords()) {
            return $this->prepareWarningResponse($this->getFirstFlightRecord());
        }

        $model = $modelClass::findOne($pk0);
        $relModel = ($relation->getRelatedModelClassName())::findOne($pk1);
        $model->removeRelated($relationName, $relModel);
        $model->save();

        return null;
	}

	public function createModel(string $serviceName, string $modelName, array $fields): ?HttpResponseInterface
	{
        $modelClass = $this->defineModelClass($serviceName, $modelName);
        if ($this->hasFlightRecords()) {
            return $this->prepareWarningResponse($this->getFirstFlightRecord());
        }

        $model = new $modelClass($fields);
        $model->save();

        return null;
	}

	public function deleteModel(string $serviceName, string $modelName, int $pk): ?HttpResponseInterface
	{
        $modelClass = $this->defineModelClass($serviceName, $modelName);
        if ($this->hasFlightRecords()) {
            return $this->prepareWarningResponse($this->getFirstFlightRecord());
        }

        $model = $modelClass::findOne($pk);
        $model->delete();

        return null;
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PROTECTED
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @param string&Model $className
     * @param array $filters
     * @return array [Model[], {int}totalCount]
     */
	protected function loadModels(string $className, array $filters = []): array
	{
	    $perPage = $filters['perPage'] ?? self::PER_PAGE_DEFAULT;
	    $condition = $filters['condition'] ?? '';
        $totalCount = $className::getCount();
		if ($perPage === null) {
		    return ($condition == '')
                ? [$className::find(), $totalCount]
                : [$className::find(['WHERE' => $condition]), $totalCount];
		}

		$page = $filters['page'] ?? 0;
		$offset = $page * $perPage;
		$limit = $offset + $perPage > $totalCount
			? $totalCount - $offset
			: $perPage;

		$filter = [
			'OFFSET' => $offset,
			'LIMIT' => $limit,
		];
		if ($condition != '') {
			$filter['WHERE'] = $condition;
		}

		return [$className::find($filter), $totalCount];
	}

    /**
     * @param string $serviceName
     * @param string $modelName
     * @return string&Model
     */
	protected function defineModelClass(string $serviceName, string $modelName): string
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            $this->addError('Service not found');
            return '';
        }

        $modelManager = $service->modelManager;
        if (!$modelManager) {
            $this->addError("Model manager for service $serviceName not found");
            return '';
        }

        /** @var string&Model $modelClass */
        $modelClass = $modelManager->getModelClassName($modelName);
        if (!$modelClass) {
            $this->addError("Model $modelName not found");
            return '';
        }

        return $modelClass;
    }

    /**
     * @param string&Model $modelClass
     * @param string $relationName
     * @return ModelRelation|null
     */
    protected function defineRelation(string $modelClass, string $relationName): ?ModelRelation
    {
        if ($modelClass == '') {
            return null;
        }

        $schema = $modelClass::getModelSchema();
        $relation = $schema->getRelation($relationName);
        if (!$relation) {
            $this->addError("Relation $relationName not found");
            return null;
        }

        return $relation;
    }
}
