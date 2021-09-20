/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 *
 * @const {lx.Collection} modelEntities
 * @const {lx.Collection} modelEntitiesForMigration
 * @const {lx.ModelListDisplayer} leftEntitiesListDisplayer
 * @const {lx.ModelListDisplayer} rightEntitiesListDisplayer
 */

const leftBox = Snippet->leftBox;
const rightBox = Snippet->rightBox;
const resizer = Snippet->resizer;

#lx:require resizer_init;

class SelectedEntity {
	constructor(displayer, collection) {
		this.displayer = displayer;
		this.collection = collection;
		this.indexes = [];
	}

	isEmpty() {
		return !this.indexes.len;
	}

	reset() {
		this.indexes.forEach(a=>this.unselect(a));
		this.indexes = [];
	}

	select(index) {
		this.indexes.push(index);
		var row = this.displayer.getRow(index);
		row.side.fill('lightgreen');
		row.body.fill('lightgreen');
	}

	unselect(index) {
		if (!this.indexes.includes(index)) return;
		var row = this.displayer.getRow(index);
		row.side.fill('');
		row.body.fill('');
		row.side->>selected.value(false);
		this.indexes.remove(index);
	}

	get() {
		var result = [];
		this.indexes.forEach(a=>result.push(this.collection.at(a)));
		return result;
	}
}

const leftSelectedEntity = new SelectedEntity(leftEntitiesListDisplayer, modelEntities);
const rightSelectedEntity = new SelectedEntity(rightEntitiesListDisplayer, modelEntitiesForMigration);

#lx:require left_box;
#lx:require right_box;

Snippet->>butToLeft.click(()=>{
	if (rightSelectedEntity.isEmpty()) {
		lx.Tost.warning( #lx:i18n(warning.no_selected_entities) );
		return;
	}

	var selected = rightSelectedEntity.get();
	rightSelectedEntity.reset();

	selected.forEach(a=>{
		modelEntitiesForMigration.remove(a);
		modelEntities.add(a);
	});
});

Snippet->>butToRight.click(()=>{
	if (leftSelectedEntity.isEmpty()) {
		lx.Tost.warning( #lx:i18n(warning.no_selected_entities) );
		return;
	}

	var selected = leftSelectedEntity.get();
	leftSelectedEntity.reset();

	selected.forEach(a=>{
		modelEntities.remove(a);
		modelEntitiesForMigration.add(a);
	});
});
