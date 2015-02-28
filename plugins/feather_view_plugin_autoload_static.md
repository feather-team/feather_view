Autoload Static
==========================

自动加载feather生成的静态资源包，可设置缓存，提升静态资源包读取速度。

###使用

```php
$view = new Feather_View;
$view->register_plugin('autoload_static', array(
    'maps' => ROOT . '/map_a.php'  //可为数组，多个数组会自动合并
    'domain' => 'http://www.baidu.com'  //静态包中的静态资源的域名
    'caching' => true,    //页面静态资源是否设置缓存，提升读取速度
    //设置缓存方式，如果需要使用自己的缓存方式，比如memcache或者redis之类，必须对Feather_View_Plugin_Cache_Abstract进行实现
    'cache' => new Feather_View_Plugin_Cache_File(array(
        'cache_dir' => 'aaa'
    ))  
));
```
