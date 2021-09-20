/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 *
 * @const {lx.Collection} modelsList
 */


// Управление видимостью блоков
#lx:require SnippetSwitcher;
const snippetSwitcher = new SnippetSwitcher();


// Кнопка создания новой модели
Snippet->>butNewModel.click(()=>{
	Plugin.root->inputPopup.open(#lx:i18n(Model name)).confirm((name)=>{
		var error = false;
		modelsList.forEach(function(model) {
			if (model.modelName == name) {
				lx.Tost.warning( #lx:i18n(warning.model_exists, {name}) );
				this.stop();
				error = true;
			}
		});
		if (error) return;

		^MainBack.createModel(name).then((res)=>{
			if (res) resetModelsList(name);
		});
	})
});


// Кнопка обновления списка моделей
Snippet->>butRenewModels.click(()=>{
	resetModelsList();
});


// Список моделей
const ModelsListBox = Snippet->>modelsList;
ModelsListBox.matrix(modelsList, (form)=>{
	form.fields({
		modelName: [ lx.Box, {width:12} ]
	});
	form.getChildren().call('border').call('align', lx.CENTER, lx.MIDDLE);

	form.setField('editStatus', function(val) {
		if (val == Model.EDIT_STATUS_NOT_CHANGED) {
			this.fill('lightgreen');
		} else if (val == Model.EDIT_STATUS_SERVER_CHANGED) {
			this.fill('yellow');
		} else if (val == Model.EDIT_STATUS_CLIENT_CHANGED) {
			this.fill('coral');
		}
	});

	form.click(()=>selectedModel.select(modelsList.at(form.matrixIndex())));
});
