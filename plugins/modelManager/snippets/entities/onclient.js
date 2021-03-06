const snippets = new lx.Dict({
	entitiesList: Snippet->>entitiesList,
	manage: Snippet->>manage
});

const modelEntities = new lx.Collection();
const modelEntitiesForMigration = new lx.Collection();
const leftEntitiesListDisplayer = new lx.ModelListDisplayer();
const rightEntitiesListDisplayer = new lx.ModelListDisplayer();


class DynamicModel extends lx.BindableModel {
	#lx:behaviors lx.BackupedModelBehavior;

	constructor(data={}) {
		super(data);
		super.afterConstruct();
	}

	static onAfterSet(field, val) {
		if (modelEntities.contains(this)) this.afterChange();
		//else this.afterChangeForMigrate();
	}

	/**
	 * Сохранение изменений без миграции
	 * */
	afterChange(field, val) {
		selectedModel.saveEntityChange(this);
	}

	//todo как-то следить за изменившимися моделями, чтобы в миграцию не попадало все подряд
	// afterChangeForMigrate(field, val) {
	// }
};


/**
 * При выделении модели запрашиваем ее сущности, имеющиеся в базе проекта
 * */
Plugin.EventSupervisor.subscribe('modelSelected', (model)=>{
	leftEntitiesListDisplayer.dropData();
	rightEntitiesListDisplayer.dropData();
	modelEntities.clear();
	modelEntitiesForMigration.clear();
	if (!model) {
		// leftEntitiesListDisplayer.reset();
		// rightEntitiesListDisplayer.reset();
		snippets.manage.clear();
		return;
	}

	^MainBack.getModelEntities(model.service, model.modelName).then((res)=>{
		DynamicModel.initSchema(res.schema);

		res.entities.each((data)=>modelEntities.add(new DynamicModel(data)));

		leftEntitiesListDisplayer.apply({data: modelEntities});
		rightEntitiesListDisplayer.apply({data: modelEntitiesForMigration});
		snippets.manage.update();
	});
});


function selectSnippet() {
	Snippet->header.getChildren().each((a, i)=>a.fill('lightgray'));
	this.fill('white');	
	snippets.each((a)=>a.hide());
	var key = this.key.split('_')[1];
	snippets[key].show();
}
Snippet->header.getChildren().each((a, i)=>a.click(selectSnippet));
selectSnippet.call(Snippet->header.child(0));
