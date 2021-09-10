/**
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:require SelectedModel;
#lx:require Model;
#lx:require ModelField;

Plugin.EventSupervisor = new lx.LocalEventSupervisor();

const modelsList = new lx.Collection();
const modelSchema = new lx.Collection();

#lx:model-collection modelsListBackup = {
	modelName,
	service,
	path,
	code,
	schema,
	needTable,
	changed,
	needMigrate
};

const selectedModel = new SelectedModel();

resetModelsList();
Plugin.EventSupervisor.subscribe(
	'migrationsAction',
	()=>resetModelsList(selectedModel.model ? selectedModel.model.modelName : null)
);

function resetModelsList(modelName = null) {
	^MainBack.getModelsData().then((res)=>{
		selectedModel.select(null);
		modelsListBackup.clear();
		modelsList.clear();
		res.each((a)=>{
			modelsListBackup.add(a.lxClone());
			modelsList.add(new Model(a));
		});
		if (modelName) {
			var subCollection = modelsList.select('modelName', modelName);
			if (subCollection.len == 1) selectedModel.select(subCollection.at(0));
		}
		Plugin.EventSupervisor.trigger('modelReselected');
	});
}
