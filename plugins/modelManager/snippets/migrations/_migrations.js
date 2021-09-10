/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.Button;

Snippet.onLoad(()=>{#lx:require onclient;});
Snippet.widget.fill('white');

var height = 40;

var menu = new lx.Box({key:'schemaHead', geom:true, height:height+'px'});
menu.gridProportional({indent:'5px'});
menu.begin();
	new lx.Button({key:'upOne',   text:#lx:i18n(up one),   width:2});
	new lx.Button({key:'upAll',   text:#lx:i18n(up all),   width:2});
	new lx.Button({key:'downOne', text:#lx:i18n(down one), width:2});
	new lx.Button({key:'downAll', text:#lx:i18n(down all), width:2});
menu.end();

var matrixScreen = new lx.Box({top:height+10+'px'});
var matrix = new lx.Box({key:'matrix', parent:matrixScreen, geom:true});
matrix.stream();
