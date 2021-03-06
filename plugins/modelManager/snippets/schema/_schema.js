/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.Button;
#lx:use lx.Input;

Snippet.onLoad(()=>{#lx:require onclient;});

var height = 40;

/******************************************************************************************************************************
 * COMMON
 *****************************************************************************************************************************/
Snippet.widget.addClass('lxDW_main_color');

var header = new lx.Box({key:'header', geom:true, height:height+'px'});
header.streamProportional({direction:lx.HORIZONTAL});
header.begin();
	new lx.Box({key:'modelNameFon'});
	var c = new lx.Collection(
		new lx.Box({key:'head_schema',    text:#lx:i18n(schema)   }),
		new lx.Box({key:'head_relations', text:#lx:i18n(relations)}),
		new lx.Box({key:'head_code',      text:#lx:i18n(code)     }),
		new lx.Box({key:'head_manage',    text:#lx:i18n(manage)   })
	);
	c.call('align', lx.CENTER, lx.MIDDLE);
	c.call('fill', 'lightgray');
	c.call('style', 'cursor', 'pointer');
header.end();

var body = new lx.Box({key:'body', top:height+'px'});
body.begin();
	new lx.Box({key:'schema',    geom:true});
	new lx.Box({key:'relations', geom:true});
	new lx.Box({key:'code',      geom:true});
	new lx.Box({key:'manage',    geom:true});
body.end();


/******************************************************************************************************************************
 * Schema
 *****************************************************************************************************************************/
var schema = body->schema;
schema.addClass('lxDW_main_color');
schema.begin();
	menu = new lx.Box({key:'schemaHead', height:height+'px', geom:true});
	menu.gridProportional({indent:'5px'});
	menu.begin();
		new lx.Button({key:'butSchemaAddField', text:#lx:i18n(add field), width:2});
		new lx.Button({key:'butSchemaDelField', text:#lx:i18n(del field), width:2});
		new lx.Button({key:'butSchemaApply',    text:#lx:i18n(apply),     width:2});
		new lx.Button({key:'butSchemaReset',    text:#lx:i18n(reset),     width:2});
	menu.end();

	var header = new lx.Box({top:height+'px', height:height+'px'});
	header.streamProportional({direction:lx.HORIZONTAL});
	header.begin();
		new lx.Box({text:#lx:i18n(Name)   });
		new lx.Box({text:#lx:i18n(Type)   });
		new lx.Box({text:#lx:i18n(Default)});
	header.end();
	header.getChildren().call('align', lx.CENTER, lx.MIDDLE);

	var schemaMatrixBox = new lx.Box({top:height*2+'px'});
	schemaMatrix = schemaMatrixBox.add(lx.Box, {key:'schemaMatrix', geom:true});
	schemaMatrix.stream();
schema.end();


/******************************************************************************************************************************
 * Relations
 *****************************************************************************************************************************/
var relations = body->relations;
relations.addClass('lxDW_main_color');


/******************************************************************************************************************************
 * Code
 *****************************************************************************************************************************/
var code = body->code;
code.addClass('lxDW_main_color');
code.begin();
	var menu = new lx.Box({key:'schemaHead', height:height+'px', geom:true});
	menu.gridProportional({indent:'5px'});
	menu.begin();
		new lx.Button({key:'butCodeApply', text:#lx:i18n(apply), width:2});
		new lx.Button({key:'butCodeReset', text:#lx:i18n(reset), width:2});
	menu.end();
	
	var redactorBox = new lx.Box({key:'redactorBox', top:height+'px'});
	redactorBox.setPlugin('lx/tools:codeRedactor');
code.end();


/******************************************************************************************************************************
 * Manage
 *****************************************************************************************************************************/
var manage = body->manage;
manage.addClass('lxDW_main_color');
manage.overflow('auto');

var manageGrid = new lx.Box({
	key: 'manageGrid',
	parent: manage,
	geom: true,
	height: 'auto'
});
manageGrid.grid({indent:'10px'});
manageGrid.begin();
	new lx.Button({key:'butManageReset',   text:#lx:i18n(reset),   width:3});
	new lx.Button({key:'butManageMigrate', text:#lx:i18n(migrate), width:3});
	new lx.Button({key:'butManageDelete',  text:#lx:i18n(delete),  width:3});
manageGrid.end();
