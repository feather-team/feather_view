<?php
class Feather_View{
    //默认后缀
    const DEFAULT_SUFFIX = '.tpl';

    private static $sendedHeader = false;

    //模版目录，可为数组
    public $template_dir = '';
    //插件目录，不可为数组
    public $plugins_dir = '';
    public $suffix = self::DEFAULT_SUFFIX;

    protected $data = array();
    protected $plugins = array();

    //设置值
    public function set($name, $value = ''){
        if(is_array($name)){
            foreach($name as $key => $value){
                $this->data[$key] = $value;
            }
        }else{
            $this->data[$name] = $value;
        }
    }

    //获取值
    public function get($name = ''){
        return $name ? $this->data[$name] : $this->data;
    }

    public function __set($name, $value = ''){
        $this->set($name, $value);
    }

    public function __get($name){
        return $this->get($name);
    }

    //执行模版返回
    public function fetch($path, $data = null, $call_plugins = true){
        $path = $this->getPath($path);
        $content = $this->loadFile($path);
        $data = $data ? $data : $this->get();

        $call_plugins && $content = $this->callPlugins($path, $content);
        
        ob_start();
        extract($data);
        eval("?> {$content}");
        $content = ob_get_clean();
        ob_end_flush();
        return $content;
    }

    //显示模版
    public function display($path, $charset = 'utf-8', $type = 'text/html'){
        if(!self::$sendedHeader){
            self::$sendedHeader = true;
            header("Content-type: {$type}; charset={$charset}");
        }

        echo $this->fetch($path);
    }

    //引入某一个文件
    public function load($path, $data = null){
        echo $this->fetch("/{$path}", $data, false);
    }

    //加载某一个文件内容
    protected function loadFile($path){
        foreach((array)$this->template_dir as $dir){
            $_path = $this->getPath($dir . '/' . $path);

            if(($content = @file_get_contents($_path)) !== false){
                break;
            }
        }

        //如果content获取不到，则直接获取path，path可为绝对路径
        if($content === false && ($content = @file_get_contents($path)) === false){
            throw new Exception($path . ' is not exists!');
        }
        
        return $content;
    }

    //获取正确的路径
    protected function getPath($path){
        if(preg_match('/\.[^\.]+$/', $path)){
            return $path;
        }

        return $path . $this->suffix;
    }

    //调用插件
    protected function callPlugins($path, $content){
        foreach($this->plugins as $plugin){
            if(!function_exists($plugin[0])){
                require "{$this->plugins_dir}/{$plugin[0]}.plugin.php";
            }

            $content = $plugin[0]($path, $content, $this, $plugin[1]);
        }

        return $content;
    }

    //注册一个插件
    public function registerPlugin($callback, $opt = array()){
        $this->plugins[] = array($callback, $opt);
    }
}
