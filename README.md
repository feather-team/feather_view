Feather View
==============

Feather View是 feather中诞生出来的一个简单的php模版引擎，使用简单方便。

###使用

```php
$view = new Feather_View;
$view->template_dir = '/';  //模版目录，可为多个，写数组即可。
$view->suffix = '.tpl'; //后缀
$view->set('name', '123');
var_dump($view->get('name'));

$view->display('index/index');  //输出index/index.tpl
```

index/index.tpl
```html
<?php echo $name;?>
```

###API

* set($key[, $value]) 设置一个值, key可以为一个数组
 
* get($key) 获取有个值

* fetch($tpl) 返回模版执行后的内容，不输出

* display($tpl[, $charset, $type])  直接输出模版内容

* load($tpl[, $data])  引入某一个文件，data缺省，如果传入data则被引入的文件中的所有变量从data中获取，如果不传则传全局的data
index/index.tpl
```php
<?php $this->load('component/common/header', array('age' => 2));?>
```

component/common/header.tpl
```php
<p><?php echo $age;?></p>
```

* registerPlugin 注册插件
Feather_View提供了强大的插件运行机制，你可以在模版文件被引入后，对该模版的内容进行任何的修改，以便完成自己的定制化，该过程发生在模版文件被引入后与模版文件被执行前，也就是说传入插件的content参数只是模版的原始内容，非模版内部变量执行后被替换的内容。

```php
require PLUGINS_PATH . '/feather_view_plugin_autoload_static.php';

$view = new Feather_View;
//注册使用一个插件
$view->registerPlugin('autoload_static', array(
    'domain': 'http://baidu.com',
    'resources': array(
        ROOT . '/map_a.php',
        ROOT . '/map_b.php'
    ),
    'caching': true	//是否使用缓存
));
```

###插件开发约定
feather view的插件可继承于Feather_View_Plugin，也可以自行实现接口约定。
```php
/*
@path:string    display文件的路径，注：此为直接传入display的路径，并非完整路径
@content:string 模版的内容 
@view:object    模版对象
*/
class Feather_View_Plugin_Autoload_Static extends Feather_View_Plugin{
	public function exec($path, $content, Feather_View $view){

	}
}
```

###插件列表

* [feather_view_plugin_autoload_static](https://github.com/feather-ui/feather_view/blob/master/plugins/feather_view_plugin_autoload_static.md)
* [feather_view_plugin_static_position](https://github.com/feather-ui/feather_View/blob/master/plugins/feather_view_plugin_static_position.md)
