Autoload Static
==========================

自动加载feather生成的静态资源包，可设置缓存，提升静态资源包读取速度。

###使用

```php
$view = new Feather_View;
$view->register_plugin('autoload_static', array(
    'resources' => ROOT . '/map_a.php'  //可为数组，多个数组会自动合并
    'domain' => 'http://www.baidu.com'  //静态包中的静态资源的域名
    'cache_dir' => CACHE_DIR    //页面读取静态资源的缓存目录，第一次访问文件后会自动生成，下次直接读取缓存文件。
));
```