[Russian version (Русская версия)](https://github.com/epicoon/lx-model/blob/master/README-ru.md)

[English version (Английская версия)](https://github.com/epicoon/lx-model/blob/master/README.md)

## Service `lx/model` for lx-platform

Contains:
* [Plugin `migrationManager`](#migrationManager)
* [Plugin `relationManager`](#relationManager)

<a name="migrationManager"><h3>Plugin `migrationManager`</h3></a>
This plugin provides GUI for model and repository statuses management for any service.<br>
To lunch use [WEB CLI](https://github.com/epicoon/lx-tools/blob/master/README-ru.md#webcli): execute command `model-migrations-manage`.

<a name="relationManager"><h3>Plugin `relationManager`</h3></a>
This plugin provides GUI for model relations management. Required attributes: `model`, `relation`<br>
You can plug it to any service as dynamic plugin with using the configuration file:
```yaml
name: some/service-name

service:
  dynamicPlugins:
    modelRelation:
      prototype: lx/model:relationManager
      attributes: { model: some/service-name.ModelName, relation: relationName }
```

Another way is to build this plugin with attributes:<br>
Creare the following method in a respondent:
```php
	public function getRelationManager() {
		$plugin = \lx::$app->getPlugin('lx/model:relationManager');
		$plugin->addAttribute('model', 'some/service-name.ModelName');
		$plugin->addAttribute('relation', 'relationName');
		$builder = new \lx\PluginBuildContext($plugin);
		return $builder->build();
	}
```
On the client side you can receive the built plugin and render it in an element:
```js
^Respondent.getRelationManager().then((result)=>{
	someBox.setPlugin(result);	
});
```
