#lx:public;

class Core {
	constructor(plugin) {
		this.plugin = plugin;

		this.serviceName = null;
		this.modelName = null;
		this.relation = null;

		this.models = [
			new ModelData(this, 0),
			new ModelData(this, 1)
		];
		this.relations = [];

		this.respondentName = this.plugin.attributes.respondentName || 'Respondent';
		delete this.plugin.attributes.respondentName;

		this.respondentPlugin = this.plugin.attributes.getRespondentPlugin
			|| function(core) {return core.plugin;};
		if (lx.isString(this.respondentPlugin))
			this.respondentPlugin = lx._f.stringToFunction(this.respondentPlugin);
		delete this.plugin.attributes.getRespondentPlugin;

		var eventHandlers = this.plugin.attributes.eventHandlers || {};
		delete this.plugin.attributes.eventHandlers;
		__initEventDispatcher(this, eventHandlers);
		plugin.eventDispatcher.trigger('start');
	}

	getRespondentPlugin() {
		return this.respondentPlugin(this);
	}

	onStart() {
		this.plugin.root.getSide(0)->>modelsGrid->paginator.elementsPerPage = this.modelData0.perPage;
		this.plugin.root.getSide(1)->>modelsGrid->paginator.elementsPerPage = this.modelData1.perPage;
	}

	setModel(serviceName, modelName) {
		this.serviceName = serviceName;
		this.modelName = modelName;
		this.models[0].serviceName = serviceName;
		this.models[0].modelName = modelName;
		this.plugin.eventDispatcher.trigger('modelChanged');
	}

	setRelation(relationName) {
		this.relation = relationName;
		if (this.relation) {
			this.plugin.eventDispatcher.trigger('refresh');
		}
		//TODO else  clear data
	}

	processError(response) {
		if (response.success === false) {
			lx.Tost.error(response.message);
			return true;
		}

		return false;
	}

	modelData(num) {
		return this.models[num];
	}

	get modelData0() { return this.models[0]; }
	get modelData1() { return this.models[1]; }

	getDisplayPares() {
		return [
			{box: this.plugin->>model0->modelsGrid, modelData: this.modelData0},
			{box: this.plugin->>model1->modelsGrid, modelData: this.modelData1}
		];
	}

	onCreateRelation(pk0, pk1) {
		this.relations.push([pk0, pk1]);
	}

	onDeleteRelation(pk0, pk1) {
		var pare = [pk0, pk1];
		var index;
		for (var i in this.relations) {
			if (this.relations[i][0] == pare[0] && this.relations[i][1] == pare[1]) {
				index = i;
				break;
			}
		}
		this.relations.splice(index, 1);
	}
}


/***********************************************************************************************************************
 * PRIVATE
 **********************************************************************************************************************/

function __initEventDispatcher(self, eventHandlers) {
	var handlers = defaultHandlers.lxMerge(eventHandlers, true);
	for (var i in handlers)
		if (lx.isString(handlers[i])) handlers[i] = lx._f.stringToFunction(handlers[i]);

	for (let i in defaultHandlers)
		self.plugin.eventDispatcher.subscribe(i, (...args)=>{
			var newArgs = [self];
			for (var j in args) newArgs.push(args[j]);
			handlers[i].apply(null, newArgs);
		});

	self.handlers = handlers;
}

const defaultHandlers = {
	start: function(core) {
		var plugin = core.getRespondentPlugin();
		plugin.ajax(core.respondentName+'.getCoreData', [plugin.attributes]).send().then((res)=>{
			core.onStart();
			var data = res.data;
			core.setModel(data.serviceName, data.modelName);
			core.setRelation(data.relation);
		});
	},

	modelChanged: function(core) {
		var modelData = core.modelData0;
		modelData.setTitle(
			'Model: <b>' + modelData.serviceName + '.' + modelData.modelName + '</b>'
		);

		//TODO clear data
	},

	refresh: function (core, condition0 = '', condition1 = '', page0 = 0, page1 = 0) {
		var modelData = core.modelData0;
		modelData.setTitle(
			'Model: <b>' + modelData.serviceName + '.' + modelData.modelName + '</b>'
			+ ', relation: <b>' + core.relation + '</b>'
		);

		var plugin = core.getRespondentPlugin();
		plugin.ajax(core.respondentName+'.getRelationData', [
			core.serviceName,
			core.modelName,
			core.relation,
			[
				{condition: condition0, page: page0, perPage: core.modelData0.perPage},
				{condition: condition1, page: page1, perPage: core.modelData1.perPage}
			]
		]).send().then((res)=>{
			if (core.processError(res)) return;

			var data = res.data;
			core.modelData1.serviceName = data.relatedServiceName;
			core.modelData1.modelName = data.relatedModelName;

			core.modelData0.setList({
				list: data.models0,
				total: data.count0
			});
			core.modelData1.setList({
				list: data.models1,
				total: data.count1
			});

			core.relations = data.relations;

			core.plugin.eventDispatcher.trigger('afterRefresh');
			core.plugin.eventDispatcher.trigger('fillBody');
		});
	},

	afterRefresh: function (core) {
		var modelData = core.modelData1;
		modelData.setTitle(
			'Model: <b>' + modelData.serviceName + '.' + modelData.modelName + '</b>'
		);
	},

	fillBody: function(core) {
		var pares = core.getDisplayPares();
		fill(pares[0].modelData, pares[0].box);
		fill(pares[1].modelData, pares[1].box);
		function fill(modelData, widget) {
			const schema = modelData.list.modelClass.schema;
			if (modelData.grid) {
				modelData.selected = null;
				modelData.grid.dropCollection();
			} else {
				modelData.grid = widget;
				modelData.grid.setCollection(modelData.list);

				let sequence = schema.getFieldNames();
				sequence.lxRemove(schema.getPkName());
				sequence = [schema.getPkName(), ...sequence];
				modelData.grid.setColumnSequence(sequence);

				modelData.grid.addColumn({
					name: '_rel',
					title: '',
					before: schema.getPkName(),
					widget: { width: '20px' },
					render: function(box, model) {
						var checkbox = box.add(lx.Checkbox, {key:'match'});
						box.align(lx.CENTER, lx.MIDDLE);
						checkbox.on('change', function() {
							if (this.value() && modelData.contrData.selected === null) {
								this.value(false);
								return;
							}

							var ids = modelData.isMain()
									? [
										model.getPk(),
										modelData.contrData.list.at(modelData.contrData.selected).getPk()
									]
									: [
										modelData.contrData.list.at(modelData.contrData.selected).getPk(),
										model.getPk(),
									],
								event = this.value() ? 'createRelation' : 'deleteRelation';
							core.plugin.eventDispatcher.trigger(event, ids);
						});
					}
				});
			}

			modelData.grid.setLockedColumn(schema.getPkName());
			modelData.grid.render();
			modelData.grid.on('rowClick', function (e) {
				var target = e.target.__lx;
				if (lx.isInstance(target, lx.Checkbox)) return;
				modelData.select(e.rowIndex);
			});
		}
	},

	selectModel: function(core, modelData, index) {
		core.handlers.unselectModel(core, modelData);
		core.handlers.unselectModel(core, modelData.contrData);
		core.handlers.dropCheckboxes(core, modelData);
		core.handlers.dropCheckboxes(core, modelData.contrData);
		modelData.grid.rowAddClass(index, 'rm-selected');
	},

	unselectModel: function(core, modelData) {
		if (modelData.selected === null) return;
		modelData.grid.rowRemoveClass(modelData.selected, 'rm-selected');
		modelData.selected = null;
	},

	dropCheckboxes: function(core, modelData) {
		for (var i=0, l=modelData.list.len; i<l; i++)
			modelData.grid.getCell('_rel', i)->match.value(false);
	},

	setCheckboxes: function(core, modelData, pks) {
		modelData.list.forEach((model, i)=>{
			if ( ! pks.includes(model.getPk())) return;
			modelData.grid.getCell('_rel', i)->match.value(true);
		});
	},

	createRelation: function(core, pk0, pk1) {
		var plugin = core.getRespondentPlugin();
		plugin.ajax(core.respondentName+'.createRelation', [
			core.serviceName, core.modelName, pk0, core.relation, pk1
		]).send().then(res=>{
			if (core.processError(res)) return;
			core.onCreateRelation(pk0, pk1);
		});
	},

	deleteRelation: function(core, pk0, pk1) {
		var plugin = core.getRespondentPlugin();
		plugin.ajax(core.respondentName+'.deleteRelation', [
			core.serviceName, core.modelName, pk0, core.relation, pk1
		]).send().then(res=>{
			if (core.processError(res)) return;
			core.onDeleteRelation(pk0, pk1);
		});
 	},

	createModel: function(core, modelData, fields, callback) {
		var plugin = core.getRespondentPlugin();
		plugin.ajax(core.respondentName+'.createModel', [
			core.serviceName, modelData.modelName, fields
		]).send().then(res=>{
			if (core.processError(res)) return;
			callback();
		});
	},

	deleteModel: function(core, modelData, index, callback) {
		var pk = modelData.list.at(index).getPk();
		var plugin = core.getRespondentPlugin();
		plugin.ajax(core.respondentName+'.deleteModel', [
			core.serviceName, modelData.modelName, pk
		]).send().then(res=>{
			if (core.processError(res)) return;
			callback();
		});
	}
};
