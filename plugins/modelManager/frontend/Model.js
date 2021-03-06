class Model extends lx.BindableModel {
	#lx:schema
		modelName,
		service,
		path,
		code,
		schema,
		needTable,
		changed,
		needMigrate,
		editStatus << __editStatus();

	/**
	 *
	 * */
	__editStatus(val) {
		if (val === undefined) {
			if (this.clientChanged) return self::EDIT_STATUS_CLIENT_CHANGED;
			if (this.needMigrate) return self::EDIT_STATUS_SERVER_CHANGED;
			return self::EDIT_STATUS_NOT_CHANGED;
		}

		switch (val) {
			case self::EDIT_STATUS_CLIENT_CHANGED:
				this.clientChanged = true;
				break;
			case self::EDIT_STATUS_SERVER_CHANGED:
				this.clientChanged = false;
				break;
			case self::EDIT_STATUS_NOT_CHANGED:
				this.clientChanged = false;
				this.needTable = false;
				this.changed = false;
				this.needMigrate = false;
				break;
		}
	}

	/**
	 *
	 * */
	resetCode() {
		this.code = this.backupModel().code;
	}	

	/**
	 *
	 * */
	changeField(field, param, value) {
		this.schema.fields[field][param] = value;
	}

	/**
	 *
	 * */
	addField(key, name, type) {
		this.schema.fields[key] = {name, type};
	}

	/**
	 *
	 * */
	delField(field) {
		delete this.schema.fields[field.key];
		field.model = null;
	}

	/**
	 *
	 * */
	hasFieldName(name) {
		for (var i in this.schema.fields)
			if (this.schema.fields[i].name == name) return true;
		return false;
	}

	/**
	 *
	 * */
	checkBackupDiffrent() {
		var diff = this.getDifference().len;
		if (!diff) {
			var backup = this.backupModel();
			if (backup) {
				var tCode = this.code.replace(/\s+/g, ' ');
				var bCode = backup.code.replace(/\s+/g, ' ');
				diff = tCode != bCode;
			}
		}

		this.editStatus = diff
			? self::EDIT_STATUS_CLIENT_CHANGED
			: (this.needMigrate ? self::EDIT_STATUS_SERVER_CHANGED : self::EDIT_STATUS_NOT_CHANGED);
	}

	/**
	 *
	 * */
	getDifference() {
		var backup = this.backupModel();
		if (!backup) return [];

		var schema = this.schema,
			oldSchema = backup.schema;

		var diffs = [];
		//todo - не предусматривалось, на бэке такого пока нет
		// if (this.name != backup.name) {
		// 	diffs.push({
		// 		action: 'renameModel',
		// 		old: backup.name,
		// 		'new': this.name
		// 	});
		// }

		var fieldNames = schema.fields.lxGetKeys(),
			oldFieldNames = oldSchema.fields.lxGetKeys(),
			addedFields = fieldNames.diff(oldFieldNames),
			deletedFields = oldFieldNames.diff(fieldNames);

		for (var i=0, l=addedFields.len; i<l; i++) {
			var params = schema.fields[addedFields[i]].lxClone();
			var name = params.lxExtract('name');
			diffs.push({
				category: 'fields',
				action: 'add_field',
				name: name,
				params 
			});
		}

		for (var i=0, l=deletedFields.len; i<l; i++) {
			diffs.push({
				category: 'fields',
				action: 'remove_field',
				name: deletedFields[i]
			});
		}

		for (var key in schema.fields) {
			if (addedFields.contains(key)) continue;
			var field = schema.fields[key],
				oldField = oldSchema.fields[key];
			if (field.name != oldField.name) {
				diffs.push({
					category: 'fields',
					action: 'rename_field',
					old: oldField.name,
					'new': field.name
				});
			}
			var cfield = field.lxClone();
			var name = cfield.lxExtract('name');
			for (var property in cfield) {
				var value = field[property];
				if (value == '--null--') value = undefined;
				if (field[property] !== oldField[property]) {
					diffs.push({
						action: 'change_field_property',
						fieldName: name,
						property,
						old: oldField[property],
						'new': field[property]
					});
				}
			}
		}

		return diffs;
	}

	/**
	 *
	 * */
	backupModel() {
		return modelsListBackup.at( modelsList.indexOf(this) );		
	}

	/**
	 *
	 * */
	static onAfterSet(field, value) {
		if (['modelName', 'code'].contains(field)) this.checkBackupDiffrent();
	}
}

Model.EDIT_STATUS_NOT_CHANGED = 1;
Model.EDIT_STATUS_SERVER_CHANGED = 2;
Model.EDIT_STATUS_CLIENT_CHANGED = 3;
