
const migrations = lx.ModelCollection.create({
	schema: [
		'name',
		'model',
		'type',
		'createdAt',
		'applied'
	]
});

Snippet->>matrix.matrix(migrations, (form)=>{
	form.fields({
		model:     [lx.Box, {width:4}],
		type:      [lx.Box, {width:4}],
		createdAt: [lx.Box, {width:4}]
	});

	form.setField('applied', function(val) {
		this.fill(val ? 'lightgreen' : '');
	});
});



//**********************************************************************************************************************
// Обработчики событий

const buttonUpOne = Snippet->>upOne;
const buttonDownOne = Snippet->>downOne;

Snippet.widget.displayIn(()=>resetMigrationsList());
Plugin.EventSupervisor.subscribe('modelReselected', ()=>{
	if (Snippet.widget.isDisplay()) resetMigrationsList();
});

buttonUpOne.click(()=>{
	if (migrations.isEmpty) {
		//TODO сообщение что нет миграций
		return;
	}

	var migration = migrations.first();
	while (migration && migration.applied) migration = migrations.next();

	if ( ! migration) {
		//TODO сообщение что нет ненакаченных миграций
		return;
	}

	buttonUpOne.disabled(true);
	^MigrationsBack.upMigration(migration.name).then((res)=>{
		buttonUpOne.disabled(false);
		if (res.success === false) {
			lx.Tost.error(#lx:i18n(Migration was not applied));
			return;
		}
		Plugin.EventSupervisor.trigger('migrationsAction');
	});
});


buttonDownOne.click(()=>{
	if (migrations.isEmpty) {
		//TODO сообщение что нет миграций
		return;
	}

	var migration = migrations.last();
	while (migration && !migration.applied) migration = migrations.prev();

	if ( ! migration) {
		//TODO сообщение что нет накаченных миграций
		return;
	}

	buttonDownOne.disabled(true);
	^MigrationsBack.downMigration(migration.name).then((res)=>{
		buttonDownOne.disabled(false);
		Plugin.EventSupervisor.trigger('migrationsAction');
	});
});



//**********************************************************************************************************************
// Функции

function resetMigrationsList() {
	migrations.clear();
	^MigrationsBack.getData().then((res)=>{
		res.forEach(a=>migrations.add(a));
	});
}
