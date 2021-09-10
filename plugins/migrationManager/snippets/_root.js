/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.Button;
#lx:use lx.TreeBox;
#lx:use lx.ActiveBox;
#lx:use lx.MultiBox;

/***********************************************************************************************************************
 * MAIN
 **********************************************************************************************************************/

var box = new lx.Box({geom: true, key:'main'});
box.gridProportional({indent: '10px', cols:3});
box.begin();
	var statusBox = new lx.Box({
		width: 3,
		text: #lx:i18n(prjStatus, {status: '...'})
	});

	new lx.Button({geom:[0, 1, 1, 1], text:#lx:i18n(renew), key:'renewBut'});
	new lx.Button({geom:[1, 1, 1, 1], text:#lx:i18n(createMigrations), key:'createBut'});
	new lx.Button({geom:[2, 1, 1, 1], text:#lx:i18n(applyMigrations), key:'applyBut'});

	new lx.TreeBox({geom:[0, 2, 3, 15], key:'tree'});
box.end();

statusBox.align(lx.LEFT, lx.MIDDLE);


/***********************************************************************************************************************
 * ACTION RESULT REPORT
 **********************************************************************************************************************/

var box = new lx.ActiveBox({
	geom: [25, 20, 50, 50],
	header: #lx:i18n(actionReport),
	closeButton: true
});
box.add(lx.Box, {key:'actionReport', geom: [0, 0, 100, 'auto']}).stream();
box.hide();


/***********************************************************************************************************************
 * INFO
 **********************************************************************************************************************/

var box = new lx.ActiveBox({
	key: 'serviceInfo',
	geom: [10, 7, 80, 80],
	header: #lx:i18n(serviceMigrations),
	closeButton: true
});
box.hide();

var wrapper = box.add(lx.Box, {geom:[0, 0, 50, 100]});
var migrationsMatrix = wrapper.add(lx.Box, {key:'migrationsMatrix'});
migrationsMatrix.stream();

var textWrapper = box.add(lx.Box, {geom:[50, 0, 50, 100]});
textWrapper.add(lx.Box, {field:'migrationText', margin:'10px', css:'lx-model-migtext'});
