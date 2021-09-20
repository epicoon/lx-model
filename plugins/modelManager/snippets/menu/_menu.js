/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.Button;
#lx:use lx.LanguageSwitcher;

Snippet.onLoad(()=>{#lx:require onclient;});

Snippet.widget.addClass('lxDW_main_color');
Snippet.widget.streamProportional({indent:'10px'});

var height = '40px';

new lx.LanguageSwitcher({height});

var vis = new lx.Box({height});
vis.gridProportional({step:'10px'});
vis.begin();
	(new lx.Collection(
		new lx.Box({field:'visModelSchema',     text:#lx:i18n(Schema),     width:4}),
		new lx.Box({field:'visModelEntities',   text:#lx:i18n(Entities),   width:4}),
		new lx.Box({field:'visModelMigrations', text:#lx:i18n(Migrations), width:4})
	)).forEach((a)=>{
		a.align(lx.CENTER, lx.MIDDLE);
		a.roundCorners('8px');
		a.style('cursor', 'pointer');
	});
vis.end();

var buts = new lx.Box({height});
buts.gridProportional({step:'10px'});
buts.begin();
	new lx.Button({
		key: 'butNewModel',
		text: #lx:i18n(New model),
		width: 6
	});
	new lx.Button({
		key: 'butRenewModels',
		text: #lx:i18n(Renew models),
		width: 6
	});
buts.end();

var modelsScreen = new lx.Box();
modelsScreen.border();
var list = new lx.Box({key:'modelsList', parent:modelsScreen});
list.stream({direction:lx.VERTICAL});

Snippet.widget.findAll('text').call('ellipsis');
