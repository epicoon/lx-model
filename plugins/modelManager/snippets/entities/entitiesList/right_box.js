/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 *
 * @const {lx.Box} rightBox
 * @const {lx.Collection} modelEntitiesForMigration
 * @const {SelectedEntity} rightEntitiesListDisplayer
 * @const {lx.ModelListDisplayer} rightEntitiesListDisplayer
 * @class DynamicModel
 */

rightEntitiesListDisplayer.init({
	lock: ['id'],
	// hide: ['id'],
	box: rightBox->list,
	modelClass: DynamicModel,
	data: modelEntitiesForMigration
});

// Чекбокс для выделения
rightEntitiesListDisplayer.addColumn({
	lock: true,
	position: lx.LEFT,
	width: '20px',
	widget: lx.Box,
	render: function(widget) {
		var checkbox = widget.add(lx.Checkbox, {key:'selected'});
		widget.align(lx.CENTER, lx.MIDDLE);
		checkbox.on('change', function() {
			if (this.value()) rightSelectedEntity.select(this.parent.parent.index);
			else rightSelectedEntity.unselect(this.parent.parent.index);
		});
	}
});

// Коллектор для хранения удаленных моделей (на случай ресета)
const garbage = new lx.Collection();

// Добавить модель
rightBox->>butEntityAdd.click(()=>{
	if (selectedModel.model === null) {
		lx.Tost.warning( #lx:i18n(warning.no_selected_model) );
		return;
	}

	var fieldNames = [];
	var defaults = {};
	for (var key in selectedModel.model.schema.fields) {
		var properties = selectedModel.model.schema.fields[key];
		fieldNames.push(properties.name);
		if (properties['default'] !== undefined)
			defaults[properties.name] = properties['default'];
	}

	Plugin.root->inputPopup.open(fieldNames, defaults).confirm((values)=>{
		if (!lx.isArray(values)) values = [values];
		var data = {};
		fieldNames.forEach((a, i)=>data[a]=values[i]);
		modelEntitiesForMigration.add(new DynamicModel(data));
	});
});

// Удалить модель
rightBox->>butEntityDel.click(()=>{
	if (rightSelectedEntity.isEmpty()) {
		lx.Tost.warning( #lx:i18n(warning.no_selected_entities) );
		return;
	}

	var entities = rightSelectedEntity.get();
	rightSelectedEntity.reset();

	entities.forEach(a=>{
		if (a.id) garbage.add(a);
		modelEntitiesForMigration.remove(a);
	});
});

// Отменить все действия
rightBox->>butEntityReset.click(()=>{
	rightSelectedEntity.reset();

	modelEntitiesForMigration.forEach(a=>{
		if (a.id) {
			a.reset();
			modelEntities.add(a);
		}
	});
	modelEntitiesForMigration.clear();

	garbage.forEach(a=>{
		a.reset();
		modelEntities.add(a);
	});
	garbage.clear();
});

// Применить изменения, сгенерировать миграцию
rightBox->>butEntityApply.click(()=>{
	if (modelEntitiesForMigration.isEmpty && garbage.isEmpty) {
		lx.Tost.warning( #lx:i18n(warning.no_entity_changes) );
		return;
	}

	var add = [], del = [], edit = [];
	modelEntitiesForMigration.forEach(a=>{
		if (a.id) edit.push([a.backup.getFields(), a.getFields()]);
		else add.push(a.getFields());
	});
	garbage.forEach(a=>del.push(a.getFields()));

	selectedModel.correctEntitiesWithMigrate(add, del, edit);
});
