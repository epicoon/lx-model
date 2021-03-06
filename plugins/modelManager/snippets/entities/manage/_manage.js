/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.Button;

Snippet.onLoad(()=>{#lx:require onclient;});

Snippet.widget.addClass('lxDW_main_color');

var menu = new lx.Box({key:'manageHead', geom:true, height:'35px'});
menu.gridProportional({indent:'5px'});
menu.begin();
	new lx.Button({key:'butManageColsApply', text:#lx:i18n(apply), width:2});
	new lx.Button({key:'butManageColsReset', text:#lx:i18n(reset), width:2});
menu.end();

Snippet.widget.findAll('text').call('ellipsis');
