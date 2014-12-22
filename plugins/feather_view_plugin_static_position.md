Static Position
======================

主要用于component单独调试使用，专为解决页面中不存在head和body结束标签而导致的无法加载静态资源的问题。

###使用
```php
$view = new Feather_View;
$view->register_plugin('static_position');

$view->display('/component/1.phtml');
```

```html
<h1>123</h1>
```

调用执行后：
```php
<?php $this->load('/component/resource/userscript', $this->get('FEATHER_USE_HEAD_SCRIPTS');?>
<?php $this->load('/component/resource/userscript', $this->get('FEATHER_USE_STYLES');?>
<h1>123</h1>
<?php $this->load('/component/resource/userscript', $this->get('FEATHER_USE_SCRIPTS');?>
```
