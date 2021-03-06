/**
 * Централизованный выключатель эктив-боксов
 * */
class SnippetSwitcher extends lx.BindableModel {
	#lx:schema
		visModelSchema,
		visModelEntities,
		visModelMigrations;

	get list() {
		if (!this.__snippetSwitchers) {
			this.__snippetSwitchers = new lx.Collection();
			self::getFieldNames().each((a)=>{
				var vis = Snippet.find(a);
				vis.getTarget = function() {
					var key = this.key.split(/^vis/)[1];
					return Plugin.find(key);
				};
				vis.click(function() { this.switcher[this.key] = !this.switcher[this.key]; });
				vis.setField(vis.key, function(val) {
					this.fill(val ? 'lightgreen' : 'orange');
					var target = this.getTarget();
					target.visibility(val);
					if (val) lx.WidgetHelper.bringToFront(target);
				});
				this.__snippetSwitchers.add(vis);
				vis.switcher = this;
				this.bind(vis);
			});
		}
		return this.__snippetSwitchers;
	}

	constructor() {
		super();
		this.list.each((a)=>{
			var target = a.getTarget();
			target->header.add(lx.Box, {
				geom: [null, '2px', '20px', '20px', '2px'],
				click: ()=>{ this['vis' + target.key] = false; },
				style: {fill:'red', cursor:'pointer'}
			});
		});
		this.visModelSchema = true;
		this.visModelEntities = true;
	}
}
