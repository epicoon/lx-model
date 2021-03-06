class ModelField extends lx.BindableModel {
	#lx:schema name, type, default;

	constructor(model, key, data) {
		super(data);
		this.model = model;
		this.key = key;
	}

	checkBackupDiffrent(property) {
		var value = this[property];
		var widgets = this.getWidgetsForField(property);
		var backValue;

		if (this.model.backupModel().schema.fields[this.key])
			backValue = this.model.backupModel().schema.fields[this.key][property];
		if (backValue === undefined) backValue = '--null--';
		widgets.each((a)=>a.toggleClassOnCondition(backValue!=value, 'lxDW_model_changed_field'));

		this.model.checkBackupDiffrent();
	}

	static onBeforeSet(property, value) {
		if (!this.model) return;
		var oldValue = this[property];
		if (property == 'name' && value != oldValue && this.model.hasFieldName(value)) {
			this[property] = oldValue;
			lx.Tost.warning( #lx:i18n(warning.model_field_unique_name) );
			return false;
		}
	}

	static onAfterSet(property, value) {
		this.model.changeField(this.key, property, value);
		this.checkBackupDiffrent(property);
	}
};
