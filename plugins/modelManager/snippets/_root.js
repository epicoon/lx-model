/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

/*
Имя обслуживаемого сервиса

Список моделей
	По каждой модели есть инфа:
	- Имя
	- Схема (таблица)
	- Код (как текст)
	- Связи с другими моделями
	- Список уже существующих в системе моделей
	- Требуется ли миграция

	Действия с моделью:
	- Инициировать миграции
	- Изменить имя
	- Изменить схему
	- Редактировать код
	- Редактировать связи с другими моделями
	- Добавить модели
	- Редактировать существующие модели
	- Удалить существующие модели

Графически изобразить взаимосвязи моделей
*/

#lx:use lx.Checkbox;
#lx:use lx.CheckboxGroup;
#lx:use lx.ActiveBox;
#lx:use lx.Form;

var menu = new lx.ActiveBox({
	key: 'menu',
	adhesive: true,
	geom:true,
	width: '20%',
	header: #lx:i18n(Menu)
});
menu.setSnippet('menu');

var schema = new lx.ActiveBox({
	key: 'ModelSchema',
	adhesive: true,
	left: '20%',
	height: '50%',
	header: #lx:i18n(Model schema)
});
schema.setSnippet('schema');

var entities = new lx.ActiveBox({
	key: 'ModelEntities',
	adhesive: true,
	coords: ['20%', '50%'],
	header: #lx:i18n(Model entities)
});
entities.setSnippet('entities');

var migrations = new lx.ActiveBox({
	key: 'ModelMigrations',
	adhesive: true,
	coords: ['20%', '50%'],
	header: #lx:i18n(Migrations)
});
migrations.setSnippet('migrations');

#lx:use lx.InputPopup;
#lx:use lx.ConfirmPopup;
new lx.InputPopup();
new lx.ConfirmPopup();
