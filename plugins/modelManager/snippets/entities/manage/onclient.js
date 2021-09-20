class ColumnsForShow extends lx.BindableModel {
	#lx:schema
		diffFields,
		showFields;

	#lx:behaviors lx.BackupedModelBehavior;

	constructor(data={}) {
		if (data.diffFields === undefined) data.diffFields = [];
		super(data);
		super.afterConstruct();
	}

	onCommit() {
		this.diffFields = [];
	}

	static backupedFields() {
		return ['showFields'];
	}

	static onAfterSet(field, val) {
		if (field == 'showFields') {
			var back = this.backup.getField(field);
			this.diffFields = [].lxMerge(val.lxDiff(back)).lxMerge(back.lxDiff(val));
		}
	}
}

const columnsForShow = new ColumnsForShow();
let columnsMap = [];


Snippet.widget.update = function() {
	if (Snippet.widget.contains('showFields')) {
		columnsForShow.unbind(Snippet->showFields);
		Snippet.widget.del('showFields');
	}

	columnsMap = DynamicModel.getFieldNames();
	var checkList = new lx.CheckboxGroup({
		parent: Snippet.widget,
		field: 'showFields',
		labels: columnsMap,
		geom: [10, 20, 80, 80]
	});

	checkList.labels().forEach(label=>{
		label.setField('diffFields', function(val) {
			this.style(
				'color',
				val.includes(this.parent.index) ? 'red' : ''
			);
		}, lx.Binder.BIND_TYPE_READ);
	});

	var showFields = new Array(columnsMap.len);
	for (let i=0; i<columnsMap.len; i++)
		showFields[i] = i;
	columnsForShow._showFields = showFields;
	columnsForShow.commit();
	columnsForShow.bind(checkList);
};

Snippet.widget.clear = function() {
	if (Snippet.widget.contains('showFields')) {
		columnsForShow.unbind(Snippet->showFields);
		Snippet.widget.del('showFields');
	}
};


Snippet->>butManageColsApply.click(()=>{
	columnsForShow.commit();
	var showList = [];
	for (var i in columnsForShow.showFields) {
		showList.push(columnsMap[columnsForShow.showFields[i]]);
	}
	leftEntitiesListDisplayer.init({ hide: columnsMap.lxDiff(showList) });
	leftEntitiesListDisplayer.reset();
	rightEntitiesListDisplayer.init({ hide: columnsMap.lxDiff(showList) });
	rightEntitiesListDisplayer.reset();
});
Snippet->>butManageColsReset.click(()=>columnsForShow.reset());
