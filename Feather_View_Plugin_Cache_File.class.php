<?php
Feather_View_Loader::import('Feather_View_Plugin_Cache_Abstract.class.php');

class Feather_View_Plugin_Cache_File extends Feather_View_Plugin_Cache_Abstract{
	const CACHE_SUFFIX = '.php';

	protected $cacheDir;

	public function __construct($opt = array()){	
		$this->cacheDir = rtrim($opt['cache_dir'], '/') . '/';
		self::mkdir($this->cacheDir);
	}

	public function write($path, $content = null, $serialize = true){
		if($serialize){
			$content = serialize($content);
		}

		return file_put_contents($this->getRealPath($path), $content);
	}

	public function read($path, $unserialize = true){
		$path = $this->getRealPath($path);

		if(is_file($path)){
			return $unserialize ? unserialize(file_get_contents($path)) : require($path);
		}else{
			return null;
		}
	}

	protected function getRealPath($path){
		return $this->cacheDir . md5(ltrim($path, '/')) . self::CACHE_SUFFIX;
	}

	public static function mkdir($dir, $mod = 0777){
	    if(is_dir($dir)){
	        return true;
	    }else{
	        $old = umask(0);

	        if(mkdir($dir, $mod, true) && is_dir($dir)){
	            umask($old);
	            return true;
	        } else {
	            umask($old);
	        }
	    }

	    return false;
	}
}