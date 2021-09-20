class SelectedModel {
	constructor() {
		this.model = null;
		this.widgets = [];

		this.fieldKeyCounter = 0;
	}

	/**
	 * Выбрать модель
	 * */
	select(model) {
		if (!model && this.model) for (var i in this.widgets) this.model.unbind(this.widgets[i]);
		this.model = model;
		if (model) for (var i in this.widgets) model.bind(this.widgets[i]);
		Plugin.EventSupervisor.trigger('modelSelected', model);
	}

	/**
	 * Обновить выделение модели
	 * */
	reselect() {
		if (!this.model) return;
		this.select(this.model);
		Plugin.EventSupervisor.trigger('modelReselected');
	}

	/**
	 * Откатить все изменения в модели
	 * */
	reset() {
		if (this.model === null) {
			lx.Tost.warning( #lx:i18n(warning.no_selected_model) );
			return;
		}

		var index = this.selectedIndex(),
			backup = modelsListBackup.at(index),
			fields = backup.getFields();
		for (var key in fields) {
			var value = fields[key];
			if (value && (lx.isObject(value) || lx.isArray(value))) this.model[key] = value.lxClone();
			else this.model[key] = value;
		}
		this.model.tableName = backup.schema.table;

		this.reselect();
		lx.Tost( #lx:i18n(warning.model_reseted, {name: this.model.modelName}) );
	}

	/**
	 * Удалить модель
	 * */
	remove() {
		if (this.model === null) {
			lx.Tost.warning( #lx:i18n(warning.no_selected_model) );
			return;
		}

		Plugin.root->confirmPopup.open( #lx:i18n(confirm.delete_model, {name: this.model.modelName})).confirm(()=>{
			^MainBack.removeModel(this.model.modelName).then((res)=>{
				modelsListBackup.removeAt(this.selectedIndex());
				modelsList.remove(this.model);
				this.select(null);
			});
		});
	}

	/**
	 * Применить изменения из схемы
	 * */
	apply() {
		if (this.model === null) {
			lx.Tost.warning( #lx:i18n(warning.no_selected_model) );
			return;
		}
		if (this.model.editStatus == Model.EDIT_STATUS_NOT_CHANGED) {
			lx.Tost.warning( #lx:i18n(warning.model_not_changed) );
			return;
		}
		if (this.model.editStatus == Model.EDIT_STATUS_SERVER_CHANGED) {
			this.migrate();
			return;
		}

		var diffs = this.model.getDifference();
		^MainBack.correctModel(this.model.service, this.model.modelName, diffs).then((res)=>{
			if (res.success === false) {
				lx.Tost.error(res.data);
				return;
			}

			this.__refreshModel(res.data);
			lx.Tost( #lx:i18n(warning.model_changed) );
		});
	}

	/**
	 * Скинуть изменения в коде
	 * */
	resetCode() {
		if (this.model === null) {
			lx.Tost.warning( #lx:i18n(warning.no_selected_model) );
			return;
		}

		this.model.resetCode();
		this.reselect();
	}

	/**
	 * Применить изменения из кода
	 * */
	applyCode() {
		if (this.model === null) {
			lx.Tost.warning( #lx:i18n(warning.no_selected_model) );
			return;
		}
		if (this.model.editStatus == Model.EDIT_STATUS_NOT_CHANGED) {
			lx.Tost.warning( #lx:i18n(warning.model_not_changed) );
			return;
		}
		if (this.model.editStatus == Model.EDIT_STATUS_SERVER_CHANGED) {
			this.migrate();
			return;
		}

		^MainBack.correctModelByCode(
			this.model.service,
			this.model.modelName,
			this.model.path,
			this.model.code
		).then((res)=>{
			if (res.success === false) {
				lx.Tost.error(res.data);
				return;
			}
			this.__refreshModel(res.data);
			lx.Tost( #lx:i18n(warning.model_changed) );
		});
	}	

	/**
	 *
	 * */
	addField() {
		if (this.model === null) {
			lx.Tost.warning( #lx:i18n(warning.no_selected_model) );
			return;
		}

		var key = '_k' + this.fieldKeyCounter++,
			name = 'new_field_name',
			temp = name,
			counter = 0;
		while (this.model.hasFieldName(temp)) temp = name + counter++;
		name = temp;

		this.model.addField(key, name, 'string');
		this.reselect();
	}

	/**
	 *
	 * */
	delField(field) {
		if (!this.model) {
			lx.Tost.warning( #lx:i18n(warning.no_selected_model) );
			return;
		}
		if (!field) {
			lx.Tost.warning( #lx:i18n(warning.no_selected_field) );
			return;
		}

		this.model.delField(field);
		this.reselect();
	}

	/**
	 * Запрос серверу проверить и накатить миграции для модели
	 * */
	migrate() {
		if (this.model === null) {
			lx.Tost.warning( #lx:i18n(warning.no_selected_model) );
			return;
		}
		if (!this.model.needMigrate) {
			lx.Tost( #lx:i18n(warning.model_not_changed) );
			return;
		}

		^MainBack.migrate(this.model.modelName).then((res)=>{
			if (!res.success) {
				lx.Tost.error(res.data);
				return;
			}
			lx.Tost( #lx:i18n(warning.migration_applied) );
			this.model.editStatus = Model.EDIT_STATUS_NOT_CHANGED;
		});
	}

	/**
	 *
	 * */
	addEntity() {
		if (this.model === null) {
			lx.Tost.warning( #lx:i18n(warning.no_selected_model) );
			return;
		}
		if (this.model.needTable) {
			lx.Tost.warning( #lx:i18n(warning.model_need_table) );
			return;
		}

		var fieldNames = [];
		var defaults = {};
		for (var key in this.model.schema.fields) {
			var properties = this.model.schema.fields[key];
			fieldNames.push(properties.name);
			if (properties['default'] !== undefined)
				defaults[properties.name] = properties['default'];
		}

		Plugin.root->inputPopup.open(fieldNames, defaults).confirm((values)=>{
			var data = {};
			if (!lx.isArray(values)) values = [values];
			for (var i in values) data[fieldNames[i]] = values[i];
			^MainBack.addModelEntity(this.model.service, this.model.modelName, data).then((res)=>{
				this.reselect();
				lx.Tost( #lx:i18n(warning.new_entity) );
			});
		});
	}

	/**
	 *
	 * */
	delEntities(entities) {
		if (!entities) {
			lx.Tost.warning( #lx:i18n(warning.no_selected_entities) );
			return;
		}

		var ids = [];
		entities.forEach(a=>ids.push(a.id));
		^MainBack.delModelEntities(this.model.service, this.model.modelName, ids).then((res)=>{
			this.reselect();
			lx.Tost( #lx:i18n(warning.delete_entity) );
		});
	}

	/**
	 *
	 * */
	 saveEntityChange(entity) {
	 	^MainBack.saveEntityChange(this.model.service, this.model.modelName, entity.getFields()).then((res)=>{
			lx.Tost( #lx:i18n(warning.save_entity) );
	 	});
	 }

	/**
	 *
	 * */
	correctEntitiesWithMigrate(add, del, edit) {
		var map = [];
		if (add.len) map.push(['add', add]);
		if (del.len) map.push(['del', del]);
		if (edit.len) map.push(['edit', edit]);

		^MainBack.correctEntitiesWithMigrate(this.model.modelName, map).then((res)=>{
			this.reselect();
			lx.Tost( #lx:i18n(warning.migration_applied) );
		});
	}

	/**
	 *
	 * */
	selectedIndex() {
		return modelsList.indexOf(this.model);
	}

	/**
	 *
	 * */
	addWidget(widget) {
		this.widgets.push(widget);
	}

	/**
	 *
	 * */
	__refreshModel(data) {
		var index = modelsList.indexOf(this.model);
		modelsListBackup.set(index, data.lxClone());
		modelsList.set(index, new Model(data));
		this.model = modelsList.at(index);
		this.reselect();
	}
};
