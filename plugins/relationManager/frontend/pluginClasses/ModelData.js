class ModelData {
	constructor(core, num) {
		this.core = core;
		this.serviceName = '';
		this.modelName = '';
		this.num = num;

		this.selected = null;
		this.perPage = 10;
	}

	setTitle(title) {
		this.core.plugin.root.getSide(this.num)->>boxName.text(title);
	}

	setList(data) {
		this.list = lx.ModelCollection.create(data.list);
		this.core.plugin.root.getSide(this.num)->>pager.setElementsCount(data.total);
	}

	get mainData() {
		return this.core.modelData(0);
	}

	get relatedData() {
		return this.core.modelData(1);
	}

	get contrData() {
		return this.core.modelData(+!this.num);
	}

	isMain() {
		return (this === this.mainData);
	}

	unselect() {
		Plugin.eventManager.trigger('unselectModel', this);
	}

	select(index) {
		Plugin.eventManager.trigger('selectModel', [this, index]);
		this.selected = index;

		var pk = this.list.at(this.selected).getPk();
		var relations = this.core.relations;
		var contrNum = +!this.num;

		var matches = [];
		for (var i in relations) {
			var pare = relations[i];
			if (pare[this.num] == pk) matches.push(pare[contrNum]);
		}

		Plugin.eventManager.trigger('setCheckboxes', [this.contrData, matches]);
	}
}

Plugin.classes.ModelData = ModelData;
