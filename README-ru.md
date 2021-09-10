[English version (Английская версия)](https://github.com/epicoon/lx-model/blob/master/README.md)

## Сервис `lx/model` для lx-платформы

Включает:
* [Плагин `migrationManager`](#migrationManager)
* [Плагин `relationManager`](#relationManager)

<a name="migrationManager"><h3>Плагин `migrationManager`</h3></a>
Плагин, предоставляющий графический интерфейс управления состоянием моделей и репозиториев любого сервиса.<br>
Чтобы запустить, нужно воспользоваться [WEB CLI](https://github.com/epicoon/lx-tools/blob/master/README-ru.md#webcli): выполнить команду `model-migrations-manage`.

<a name="relationManager"><h3>Плагин `relationManager`</h3></a>
Плагин, предоставляющий графический интерфейс управления связями моделей. Получает атрибуты: `model`, `relation`<br>
Можно подключить к нужному сервису как динамический плагин, указав в его конфигурации:
```yaml
name: some/service-name

service:
  dynamicPlugins:
    modelRelation:
      prototype: lx/model:relationManager
      attributes: { model: some/service-name.ModelName, relation: relationName }
```

Другой способ - собрать этот плагин, передав аргументы:<br>
В респонденте можно создать метод:
```php
	public function getRelationManager() {
		$plugin = $this->app->getPlugin('lx/model:relationManager');
		$plugin->addAttribute('model', 'some/service-name.ModelName');
		$plugin->addAttribute('relation', 'relationName');
		$builder = new \lx\PluginBuildContext($plugin);
		return $builder->build();
	}
```
На стороне клиента можно получить результат сборки плагина и отрендерить в элемент:
```js
^Respondent.getRelationManager().then((result)=>{
	someBox.setPlugin(result);	
});
```
