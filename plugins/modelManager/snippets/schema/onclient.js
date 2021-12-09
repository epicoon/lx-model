/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

/***********************************************************************************************************************
 * COMMON
 **********************************************************************************************************************/
const modelNameFon = Snippet->>modelNameFon;
const snippets = new lx.Dict({
	schema: Snippet->>schema,
	relations: Snippet->>relations,
	code: Snippet->>code,
	manage: Snippet->>manage
});

selectedModel.addWidget(Snippet.widget);

new lx.Input({
	parent: modelNameFon,
	geom: ['2%', '10%', '96%', '80%'],
	field: 'modelName'
});

modelNameFon.setField('editStatus', function(val) {
	if (val == Model.EDIT_STATUS_NOT_CHANGED) {
		this.fill('lightgreen');
	} else if (val == Model.EDIT_STATUS_SERVER_CHANGED) {
		this.fill('yellow');
	} else if (val == Model.EDIT_STATUS_CLIENT_CHANGED) {
		this.fill('coral');
	}
});

function selectSnippet() {
	Snippet->header.getChildren().forEach((a, i)=>{if(!i)return;a.fill('lightgray')});
	this.fill('white');	
	snippets.forEach(a=>a.hide());
	var key = this.key.split('_')[1];
	snippets[key].show();
}
Snippet->header.getChildren().forEach((a, i)=>{
	if (!i) return;
	a.click(selectSnippet);
});
selectSnippet.call(Snippet->header.child(1));


/***********************************************************************************************************************
 * Schema
 **********************************************************************************************************************/
const schemaMatrix = Snippet->>schemaMatrix;
let selectedSchemaItem = -1;
function selectSchemaItem(form) {
	if (selectedSchemaItem != -1) schemaMatrix.child(selectedSchemaItem).fill('');
	selectedSchemaItem = form.index;
	schemaMatrix.child(selectedSchemaItem).fill('lightgreen');
}

snippets.schema->>butSchemaAddField.click( ()=>selectedModel.addField()                                   );
snippets.schema->>butSchemaDelField.click( ()=>selectedModel.delField(modelSchema.at(selectedSchemaItem)) );
snippets.schema->>butSchemaApply.click(    ()=>selectedModel.apply()                                      );
snippets.schema->>butSchemaReset.click(    ()=>selectedModel.reset()                                      );

schemaMatrix.matrix(modelSchema, (form)=>{
	form.fields({
		name: [lx.Input, {width:4}],
		type: [lx.Input, {width:4}],
		default: [lx.Input, {width:4}]
	});
	form.click(()=>selectSchemaItem(form));
	form.getChildren().forEach(child=>child.on('focus', ()=>selectSchemaItem(form)));
}, (form, field)=>{
	form.getChildren().forEach(a=>field.checkBackupDiffrent(a._field));	
});

Plugin.EventSupervisor.subscribe('modelSelected', (model)=>{
	modelSchema.clear();
	selectedSchemaItem = -1;

	if (!model) return;

	var fields = model.schema.fields;
	var schema = new lx.Dict();
	for (var i in fields) {
		var field = fields[i];
		var item = {};
		item.name = field.name;
		item.type = field.type;
		item.default = (field.default) ? field.default : '--null--';
		schema[i] = item;
	}
	schema.forEach((a, i)=>modelSchema.add(new ModelField(model, i, a)));
});


/***********************************************************************************************************************
 * Relations
 **********************************************************************************************************************/


/***********************************************************************************************************************
 * Code
 **********************************************************************************************************************/
snippets.code.setField('code', function(val) {
	let redactor = this->>redactor;
	if (val === undefined) return redactor.getText();
	redactor.lang = 'php';
	redactor.setText(val);
});
snippets.code->>textbox.on('blur', ()=>snippets.code.trigger('change'));
snippets.code->>butCodeApply.click(()=>selectedModel.applyCode());
snippets.code->>butCodeReset.click(()=>selectedModel.resetCode());


/***********************************************************************************************************************
 * Manage
 **********************************************************************************************************************/
snippets.manage->>butManageReset.click(()=>selectedModel.reset());
snippets.manage->>butManageMigrate.click(()=>selectedModel.migrate());
snippets.manage->>butManageDelete.click(()=>selectedModel.remove());
