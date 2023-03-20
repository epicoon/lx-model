/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.Button;
#lx:use lx.Input;
#lx:use lx.ModelCollectionGrid;

Snippet.widget.addClass('rm-side');
Snippet.widget.streamProportional({indent: '10px'});

var height = '40px';

// Имя модели
var name = new lx.Box({key: 'boxName', height});

// Кнопки создания, удаления
var buts = new lx.Box({height});
buts.gridProportional({cols:2, step:'10px'});
buts.begin();
	var butAdd = new lx.Button({key: 'butCreate', text: 'create'});
	var butDel = new lx.Button({key: 'butDelete', text: 'delete'});
buts.end();

// Фильтры
var filter = new lx.Box({key: 'filter'});
filter.grid({step:'10px', minHeight:height});
filter.begin();
	var lbl = new lx.Box({text:'Filters:', width:2});
	lbl.align(lx.CENTER, lx.MIDDLE);
	new lx.Input({key:'inpFilter', width:8});
	new lx.Button({key:'butApplyFilter', text:'apply', width:2});
filter.end();

// Основной бокс и пагинатор
var body = new lx.ModelCollectionGrid({key: 'modelsGrid'});

name.align(lx.CENTER, lx.MIDDLE);

Snippet.onLoad(()=>{
	Snippet->>butCreate.click(()=>{
		const modelData = Plugin.core.modelData(Snippet.attributes.num);
		var fieldNames = [];
		var defaults = {};
		for (var key in modelData.list.modelClass.schema.fields) {
			var properties = modelData.list.modelClass.schema.fields[key];

			if (properties.type == 'pk') continue;
			fieldNames.push(key);
			if (properties['default'] !== undefined)
				defaults[key] = properties['default'];
		}

		lx.InputPopup.open(fieldNames, defaults).confirm(values=>{
			var fields = {};
			if (!lx.isArray(values)) values = [values];
			for (var i in values) fields[fieldNames[i]] = values[i];

			Plugin.eventDispatcher.trigger('createModel', [
				modelData,
				fields,
				()=>Plugin.eventDispatcher.trigger('refresh', getRefreshArguments())
			]);
		});
	});

	Snippet->>butDelete.click(()=>{
		const modelData = Plugin.core.modelData(Snippet.attributes.num);

		if (modelData.selected === null) {
			lx.tostWarning('Model is not selected');
			return;
		}

		Plugin.eventDispatcher.trigger('deleteModel', [
			modelData,
			modelData.selected,
			()=>{
				Plugin.eventDispatcher.trigger('unselectModel', modelData);
				Plugin.eventDispatcher.trigger('refresh', getRefreshArguments());
			}
		]);
	});

	Snippet->>modelsGrid->paginator.on('change', ()=>Plugin.eventDispatcher.trigger('refresh', getRefreshArguments()));
	Snippet->>butApplyFilter.click(()=>Plugin.eventDispatcher.trigger('refresh', getRefreshArguments()));

	function getRefreshArguments() {
		return Snippet.attributes.num
			? [
				Plugin.root.getContrSide(Snippet)->>inpFilter.value(),
				Snippet->>inpFilter.value(),
				Plugin.root.getContrSide(Snippet)->>modelsGrid->paginator.activePage,
				Snippet->>modelsGrid->paginator.activePage
			]
			: [
				Snippet->>inpFilter.value(),
				Plugin.root.getContrSide(Snippet)->>inpFilter.value(),
				Snippet->>modelsGrid->paginator.activePage,
				Plugin.root.getContrSide(Snippet)->>modelsGrid->paginator.activePage
			]
		;
	}
});
