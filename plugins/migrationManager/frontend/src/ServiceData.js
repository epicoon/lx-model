class ServiceData extends lx.BindableModel #lx:namespace lx.models {
	#lx:const
		STATUS_NEED_MIGRATIONS = 1,
		STATUS_NEED_UPDATE = 2,
		STATUS_ACTUAL = 3;

	#lx:schema
		status;

	constructor(category, data) {
		super();
		this.category = category;
		this.serviceName = data.serviceName;
		this.setReport(data.report);
	}

	setReport(report) {
		this.report = report;
		if (this.report.unappliedMigrations.len) {
			this.status = self::STATUS_NEED_MIGRATIONS;
		} else if (
			this.report.modelsNeedUpdate.len
			|| this.report.modelsNeedTable.len
			|| !this.report.modelsChanged.lxEmpty
		) {
			this.status = self::STATUS_NEED_UPDATE;
		} else {
			this.status = self::STATUS_ACTUAL;
		}

		(this.status == self::STATUS_ACTUAL)
			? this.category.removeServiceNeedUpdate(this)
			: this.category.addServiceNeedUpdate(this);
	}

	getTitle() {
		return this.serviceName;
	}

	hasStatus() {
		return true;
	}
}
