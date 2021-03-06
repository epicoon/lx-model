/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

Snippet.onLoad(()=>{#lx:require onclient;});

Snippet.widget.addClass('lxDW_main_color');

var height = 40;

var header = new lx.Box({key:'header', height:height+'px', geom:true});
header.streamProportional({direction:lx.HORIZONTAL});
header.begin();
	var c = new lx.Collection(
		new lx.Box({key:'head_entitiesList', text:#lx:i18n(entities list)}),
		new lx.Box({key:'head_manage',       text:#lx:i18n(manage)       })
	);
	c.call('align', lx.CENTER, lx.MIDDLE);
	c.call('fill', 'lightgray');
header.end();

var body = new lx.Box({key:'body', top:height+'px'});
body.begin();
	Snippet.addSnippet('entitiesList');
	Snippet.addSnippet('manage');
body.end();
