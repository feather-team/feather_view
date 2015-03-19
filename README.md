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

* 插件机制
Feather_View提供了强大的插件注入机制，分为2种：

1. registerPlugin 注册系统级插件，此种插件的开发已见[插件开发约定文档]
你可以在模版文件被引入后，对该模版的内容进行任何的修改，以便完成自己的定制化，该过程发生在模版文件被引入后与模版文件被执行前，也就是说传入插件的content参数只是模版的原始内容，非模版内部变量执行后被替换的内容。

```php
require PLUGINS_PATH . '/feather_view_plugin_autoload_static.php';

$view = new Feather_View;
//注册一个系统级插件
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
feather view的插件可继承于Feather_View_Plugin_Abstract,此种插件可被注册为一个系统级插件，在display、fetch或者load时被调用，也可以自行实现接口约定，使用plugin API调用.
```php
/*
该插件可被注册为系统插件

@content:string 模版的内容 
@info:array		info种提供了一些模板的信息，比如path，是否使用load方式执行之类
*/
class Feather_View_Plugin_Autoload_Static extends Feather_View_Plugin_Abstract{
	public function exec($content, $info){

	}
}
```

```php
/*
工具插件
*/

class Feather_View_Plugin_Util{
	public function xssEncode($data){
		return htmlentities($data);
	}

	public function jsonEncode($data){
		return json_encode($data);
	}
}

/*
该插件调用方式如下：

<div><?php echo $this->plugin('util')->xssEncode("<script>alert(123);</script>")?></div>
*/
```