/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.Button;

Snippet.onLoad(()=>{#lx:require _onclient;});

Snippet.widget.addClass('lxDW_main_color');

var height = 40;

var left = new lx.Box({
	key: 'leftBox',
	geom: true,
	style: {overflow:'auto'}
});

var right = new lx.Box({
	key: 'rightBox',
	geom: true,
	style: {overflow:'auto'}
});

var resizer = new lx.Box({
	key: 'resizer',
	geom: true,
	width: '10px',
	style: {
		fill: 'lightgray',
		cursor: 'col-resize',
		overflow: 'visible'
	}
});

var buts = resizer.add(lx.Box, {
	key: 'buts',
	geom: ['-15px', '120px', '40px', '80px'],
	style: {fill:'lightgray', roundCorners:'10px'}
});

buts.add(lx.Button, {
	key: 'butToRight',
	geom: [15, 10, 70, 35],
	text: '>'
});
buts.add(lx.Button, {
	key: 'butToLeft',
	geom: [15, 55, 70, 35],
	text: '<'
});


left.begin();
	(new lx.Box({text:#lx:i18n(Actions without migrations), height:height+'px'})).align(lx.CENTER, lx.MIDDLE);

	var menu = new lx.Box({top:height+'px', height:height+'px'});
	menu.gridProportional({indent:'5px'});
	menu.begin();
		new lx.Button({key:'butEntityAdd', text:#lx:i18n(add entity), width:6});
		new lx.Button({key:'butEntityDel', text:#lx:i18n(del entity), width:6});
	menu.end();

	new lx.Box({key:'list', top:height*2+10+'px'});
left.end();


right.begin();
	(new lx.Box({text:#lx:i18n(Actions with migrations), height:height+'px'})).align(lx.CENTER, lx.MIDDLE);

	var menu = new lx.Box({top:height+'px', height:height+'px'});
	menu.gridProportional({indent:'5px'});
	menu.begin();
		new lx.Button({key:'butEntityAdd',   text:#lx:i18n(add entity), width:3});
		new lx.Button({key:'butEntityDel',   text:#lx:i18n(del entity), width:3});
		new lx.Button({key:'butEntityApply', text:#lx:i18n(apply),      width:3});
		new lx.Button({key:'butEntityReset', text:#lx:i18n(reset),      width:3});
	menu.end();

	new lx.Box({key:'list', top:height*2+10+'px'});
right.end();

Snippet.widget.findAll('text').call('ellipsis');
