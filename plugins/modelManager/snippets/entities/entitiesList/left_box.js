/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 *
 * @const {SelectedModel} selectedModel
 * @const {lx.Box} leftBox
 * @const {lx.Collection} modelEntities
 * @const {lx.ModelListDisplayer} leftEntitiesListDisplayer
 * @const {SelectedEntity} leftSelectedEntity
 * @class DynamicModel
 */

leftEntitiesListDisplayer.init({
	lock: ['id'],
	// hide: ['id'],
	box: leftBox->list,
	modelClass: DynamicModel,
	data: modelEntities
});

// Чекбокс для выделения
leftEntitiesListDisplayer.addColumn({
	lock: true,
	position: lx.LEFT,
	width: '20px',
	widget: lx.Box,
	render: function(widget) {
		var checkbox = widget.add(lx.Checkbox, {key:'selected'});
		widget.align(lx.CENTER, lx.MIDDLE);
		checkbox.on('change', function() {
			if (this.value()) leftSelectedEntity.select(this.parent.parent.index);
			else leftSelectedEntity.unselect(this.parent.parent.index);
		});
	}
});


leftBox->>butEntityAdd.click(()=>selectedModel.addEntity());
leftBox->>butEntityDel.click(()=>{
	if (leftSelectedEntity.isEmpty()) {
		lx.Tost.warning( #lx:i18n(warning.no_selected_entities) );
		return;
	}

	let entities = leftSelectedEntity.get();
	leftSelectedEntity.reset();
	selectedModel.delEntities(entities);
});
