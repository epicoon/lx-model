#lx:private;

class Context #lx:namespace lx.models {
	constructor(plugin) {
		plugin.context = this;
	
		this.plugin = plugin;
		this.migrationsInfo = new lx.models.MigrationsInfo(this);
		this.widgets = {
			treeBox: plugin->>tree,
			actionReportBox: plugin->>actionReport,
			serviceInfoBox: plugin->>serviceInfo
		};
		__initWidgets(this);
	}

	renew() {
		^Respondent.getServicesData().then(res=>{
			var tree = new lx.Tree();
			var treeBox = this.widgets.treeBox;

			res.data.each(item=>{
				var serviceCategoryKey = item.serviceCategory.replace('/', '_');
				if (!tree.has(serviceCategoryKey)) {
					var node = tree.add(serviceCategoryKey);
					node.data = new lx.models.ServiceCategoryData(item);
				}

				var categoryNode = tree.get(serviceCategoryKey);

				var serviceKey = item.serviceName.replace('/', '_');
				var serviceNode = categoryNode.add(serviceKey);
				serviceNode.data = new lx.models.ServiceData(categoryNode.data, item);
			});

			treeBox.setData(tree);
		});
	}

	processMigrationErrors(serviceName, errorReport) {
		let report = {
			service: serviceName,
			actions: []
		};

		if (errorReport.errors && errorReport.errors.len) report.actions.push({
			title: 'Model update error',
			data: errorReport.errors
		});

		if (errorReport.migrationErrors) errorReport.migrationErrors.each(error=>{
			report.actions.push({
				title: 'Migration ' + error.migration + ' error',
				data: [error.error]
			});
		});

		this.widgets.actionReportBox.run([report]);
	}
}


/***********************************************************************************************************************
 * PRIVATE
 **********************************************************************************************************************/

function __initWidgets(self) {
	self.widgets.treeBox.setLeafConstructor(function(leaf) {
		var model = leaf.node.data;

		leaf->label.text(model.getTitle());

		if (model.hasStatus()) {
			leaf.createButton({key:'renew', css: 'lx-model-renew'});
			leaf.createButton({key:'createMigrations', css: 'lx-model-gen'});
			leaf.createButton({key:'upMigrations', css: 'lx-model-up'});
			leaf.createButton({key:'info', css: 'lx-model-info'});
			__initTreeButtons(leaf);
		} else {
			leaf.createChild({
				field: 'count',
				width: 'auto',
				style: {fill:'white'} //TODO css
			});
			leaf.bind(leaf.node.data);
		}
	});

	self.widgets.actionReportBox.run = function(data) {
		this.clear();
		this.useRenderCache();
		data.each(service=>{
			var name = this.add(lx.Box, {text:service.service, css:'lx-model-action-report-service'});
			name.align(lx.CENTER, lx.MIDDLE);
			service.actions.each(action=>{
				this.add(lx.Box, {text:action.title, css:'lx-model-action-report-title'});
				action.data.each(row=>{
					this.add(lx.Box, {text:row, css:'lx-model-action-report-row'});
				});
			});
		});
		this.applyRenderCache();
		this.parent.parent.show();
	};

	self.widgets.serviceInfoBox.run = function(serviceData) {
		self.migrationsInfo.reset(serviceData);
		this.show();
	};
	self.widgets.serviceInfoBox->>migrationsMatrix.matrix({
		items: self.migrationsInfo.migrations,
		itemBox: [lx.Box, {height:'auto'}],
		itemRender: function (box, model) {
			var intention = box.add(lx.Box);
			intention.grid({indent: '10px'});
			intention.begin();

			var nameWrapper = new lx.Box({width:11});
			nameWrapper.setField('isApplied', function(val) {
				this.clearClasses();
				this.addClass(val ? 'lx-model-mapplied' : 'lx-model-munapplied');
			});
			var name = nameWrapper.add(lx.Box, {field:'name', geom:true});
			name.align(lx.LEFT, lx.MIDDLE);

			var but = new lx.Box({width: 1});
			but.setField('isApplied', function(val) {
				this.clearClasses();
				this.addClass(val ? 'lx-model-down' : 'lx-model-up');
			});
			but.on('mouseover', function() {
				self.migrationsInfo.checkIntentions(model);
			});
			but.on('mouseout', function() {
				self.migrationsInfo.dropIntentions();
			});
			but.click(function(e) {
				e.stopPropagation();
				self.migrationsInfo.swapMigration(model);
			});

			intention.setField('intention', function(val) {
				this.removeClass('lx-model-iup', 'lx-model-idown');
				if (val == lx.models.MigrationsInfo.INTENTION_UP)
					this.addClass('lx-model-iup');
				else if (val == lx.models.MigrationsInfo.INTENTION_DOWN)
					this.addClass('lx-model-idown');
			});

			intention.end();

			box.setField('selected', function(val) {
				this.fill(val ? 'green' : '');
			});
			box.click(function() {
				self.migrationsInfo.selectMigration(model);
			});
		}
	});
	self.widgets.serviceInfoBox->>migrationText.bind(self.migrationsInfo.migrationText);
}

function __initTreeButtons(leaf) {
	leaf->renew.click(__leafRenewHandler);
	leaf->upMigrations.click(__leafUpMigrationsHandler);
	leaf->createMigrations.click(__leafCreateMigrationsHandler);
	leaf->info.click(__leafInfoHandler);


	leaf->upMigrations.setField('status', function(status) {
		this.disabled(status != lx.models.ServiceData.STATUS_NEED_MIGRATIONS);
	});
	leaf->createMigrations.setField('status', function(status) {
		this.disabled(status != lx.models.ServiceData.STATUS_NEED_UPDATE);
	});

	leaf.bind(leaf.node.data);
}


function __leafRenewHandler(e) {
	let model = this.parent.node.data;
	^Respondent.renewServiceData(model.getTitle()).then(res=>{
		model.setReport(res.data.report);
	});
}

function __leafCreateMigrationsHandler(e) {
	let model = this.parent.node.data;
	^Respondent.createMigrations(model.getTitle()).then(res=>{
		var data = res.data;
		if (data.actionReport.errors.len) {
			this.getPlugin().context.processMigrationErrors(model.getTitle(), data.actionReport);
			return;
		}

		model.setReport(data.serviceState.report);
		var report = new lx.models.ActionReport(model.getTitle());
		if (!data.actionReport.modelsCreated.lxEmpty()) {
			report.addAction(
				#lx:i18n(modelsCreated),
				data.actionReport.modelsCreated.lxGetKeys()
			);
		}
		if (!data.actionReport.mediatorCreated.lxEmpty()) {
			report.addAction(
				#lx:i18n(mediatorCreated),
				data.actionReport.mediatorCreated.lxGetKeys()
			);
		}
		if (!data.actionReport.mediatorUpdated.lxEmpty()) {
			report.addAction(
				#lx:i18n(mediatorUpdated),
				data.actionReport.mediatorUpdated.lxGetKeys()
			);
		}
		if (data.actionReport.newMigrations && data.actionReport.newMigrations.len) {
			report.addAction(
				#lx:i18n(newMigrations),
				data.actionReport.newMigrations
			);
		}
		this.getPlugin().context.widgets.actionReportBox.run([report]);
	});
}

function __leafUpMigrationsHandler(e) {
	let model = this.parent.node.data;
	^Respondent.runMigrations(model.getTitle()).then(res=>{
		var data = res.data;
		if (data.actionReport.migrationErrors.len) {
			this.getPlugin().context.processMigrationErrors(model.getTitle(), data.actionReport);
			return;
		}

		model.setReport(data.serviceState.report);

		var report = new lx.models.ActionReport(model.getTitle());
		report.addAction(
			#lx:i18n(migrationsApplied),
			data.actionReport.appliedMigrations
		);
		this.getPlugin().context.widgets.actionReportBox.run([report]);
	});
}

function __leafInfoHandler(e) {
	let model = this.parent.node.data;
	if (model.status == lx.models.ServiceData.STATUS_NEED_UPDATE) {
		var report = new lx.models.ActionReport(model.getTitle());
		if (model.report.modelsNeedUpdate.len) {
			report.addAction(
				#lx:i18n(modelsNeedUpdate),
				model.report.modelsNeedUpdate
			);
		}
		if (model.report.modelsNeedTable.len) {
			report.addAction(
				#lx:i18n(modelsNeedTable),
				model.report.modelsNeedTable
			);
		}
		if (!model.report.modelsChanged.lxEmpty()) {
			report.addAction(
				#lx:i18n(modelsChanged),
				model.report.modelsChanged.lxGetKeys()
			);
		}
		this.getPlugin().context.widgets.actionReportBox.run([report]);
	} else {
		this.getPlugin().context.widgets.serviceInfoBox.run(model);		
	}
}
