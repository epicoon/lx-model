class Migration extends lx.BindableModel {
	#lx:schema
		name,
		isApplied,
		selected: {default: false},
		intention: {default: 'no'};
}

class MigrationText extends lx.BindableModel {
	#lx:schema
		migrationText;

	init() {
		this.migrationText = '';
		this.selectedMigration = null;
	}

	reset() {
		this.init();
	}

	setText(text) {
		this.migrationText = text;
	}
}

#lx:namespace lx.models;
class MigrationsInfo {
	#lx:const
		INTENTION_NO = 'no',
		INTENTION_UP = 'up',
		INTENTION_DOWN = 'down';

	constructor(context) {
		this.context = context;
		this.migrations = new lx.Collection();
		this.serviceData = null;
		this.migrationText = new MigrationText();
	}

	reset(serviceData) {
		this.serviceData = serviceData;
		^Respondent.getServiceMigrations(serviceData.getTitle()).then(res=>{
			this.migrations.clear();
			res.data.forEach(data=>this.migrations.add(new Migration(data)));
		});
	}

	selectMigration(migration) {
		^Respondent.getMigrationText(this.serviceData.getTitle(), migration.name).then(res=>{

			if (this.migrationText.selectedMigration)
				this.migrationText.selectedMigration.selected = false;
			this.migrationText.selectedMigration = migration;
			migration.selected = true;
			var text = '<pre>' + res.data + '</pre>';
			this.migrationText.setText(text);
		});
	}

	checkIntentions(migration) {
		if (migration.isApplied) {
			var match = false;
			this.migrations.forEach(m=>{
				if (match) return;
				if (m.isApplied) m.intention = self::INTENTION_DOWN;
				if (m === migration) match = true;
			});
		} else {
			var match = false;
			this.migrations.forEach(m=>{
				if (m === migration) match = true;
				if (!match) return;
				if (!m.isApplied) m.intention = self::INTENTION_UP;
			});
		}
	}

	dropIntentions() {
		this.migrations.forEach(migration=>migration.intention = self::INTENTION_NO);
	}

	swapMigration(migration) {
		var count = 0;
		this.migrations.forEach(m=>{
			if (m.intention != self::INTENTION_NO) count++;
		});

		if (migration.isApplied) {
			^Respondent.rollbackMigrations(this.serviceData.getTitle(), count).then(res=>{
				var data = res.data
				if (data.actionReport.migrationErrors.len) {
					this.context.processMigrationErrors(this.serviceData.getTitle(), data.actionReport);
					return;
				}

				this.serviceData.setReport(data.serviceState.report);
				var list = data.actionReport.appliedMigrations;
				this.migrations.forEach(m=>{
					if (list.includes(m.name))
						m.isApplied = false;
				});
				this.dropIntentions();
				this.checkIntentions(migration);
			});
		} else {
			^Respondent.runMigrations(this.serviceData.getTitle(), count).then(res=>{
				var data = res.data;
				if (data.actionReport.migrationErrors.len) {
					this.context.processMigrationErrors(this.serviceData.getTitle(), data.actionReport);
					return;
				}

				this.serviceData.setReport(data.serviceState.report);
				var list = data.actionReport.appliedMigrations;
				this.migrations.forEach(m=>{
					if (list.includes(m.name))
						m.isApplied = true;
				});
				this.dropIntentions();
				this.checkIntentions(migration);
			});
		}
	}
}
