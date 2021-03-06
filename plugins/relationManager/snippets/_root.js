/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.Form;
#lx:use lx.Checkbox;

var box = new lx.Box({geom:true});
box.gridProportional({cols:2, step: '15px'});

box.begin();
	var model0 = new lx.Box({key: 'model0'});
	model0.setSnippet({
		path: 'side',
		attributes: {num: 0}
	});
	var model1 = new lx.Box({key: 'model1'});
	model1.setSnippet({
		path: 'side',
		attributes: {num: 1}
	});
box.end();

Snippet.addSnippet({plugin:'lx/tools:snippets',snippet:'inputPopup'});

Snippet.onLoad(()=>{
	Snippet.widget.getSide = function(num) {
		return this.find('model' + num);
	};

	Snippet.widget.getContrSide = function(snippet) {
		var key = 'model' + (+!snippet.attributes.num);
		return this.find(key);
	};
});
