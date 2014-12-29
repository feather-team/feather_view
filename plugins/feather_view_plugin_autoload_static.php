<?php
/*
自动加载动态资源插件
*/
class Feather_View_Plugin_Autoload_Static extends Feather_View_Plugin_Abstract{
	private $map;
	private $commonMap;
	private $domain;
	private $cache_dir;

	protected function initialize(){
		if($domain = $this->getOption('domain')){
			$this->domain = $domain;
		}else{
			$this->domain = '';
		}

		$this->cache_dir = $this->getOption('cache_dir');
	}

	private function initMap(){
		if(!$this->map){
			$array = array();

			//合并map表
			foreach((array)$this->getOption('resources') as $resource){
				$resource = require($resource);
				$array = array_merge_recursive($array, $resource);
			}

			$this->map = $array['map'];
			$this->commonMap = $array['commonMap'];
		}
	}

	//获取页面所有的静态资源
	private function getResources($path){
		$selfMap = isset($this->map[$path]) ? $this->map[$path] : array();

		if(isset($selfMap['components'])){
			$componentsMap = array();

			foreach($selfMap['components'] as $components){
				$componentsMap = array_merge_recursive($componentsMap, $this->getResources($components));
			}

			return array_merge_recursive($componentsMap, $selfMap);
		}

		return $selfMap;
	}

	//获取静态资源正确的url
	private function getUrl($resources){
		$tmp = array();
		$maps = $this->map;

		foreach($resources as $v){
			if(isset($maps[$v])){
				$info = $maps[$v];

				if(isset($info['deps'])){
					$tmp = array_merge($tmp, $this->getUrl($info['deps']));
				}

				$tmp[] = $this->domain . $info['url'];
			}else{
				$tmp[] = $v;
			}
		}

		return array_unique($tmp);
	}

	//获取require中的所有map信息和deps信息
	private function getRequireMD($deps){
		$mapResult = array(); 
		$depResult = array();
		$tmpDeps = array();
		$maps = $this->map;

		foreach($deps as $m){
			if(isset($maps[$m])){
				$v = $maps[$m];

				$url = $v['url'];

				if(!isset($mapResult[$url])){
					$mapResult[$url] = array();
				}

				$mapResult[$url][] = $m;

				//deps
				if(isset($v['deps'])){
					$deps = $v['deps'];

					//只将mod收集至deps中，以处理性能优化
					if(isset($v['isMod'])){
						$depResult[$m] = $deps;
					}

					$tmpDeps = array_merge($tmpDeps, $deps);
				}
			}
		}

		$return = array('map' => $mapResult, 'deps' => $depResult);

		if(count($tmpDeps) > 0){
			$return = array_merge_recursive($return, $this->getRequireMD($tmpDeps, $maps));
		}

		foreach($return['map'] as $key => $value){
			$return['map'][$key] = array_values(array_unique($value));
		}

		foreach($return['deps'] as $key => $value){
			$return['deps'][$key] = array_values(array_unique($value));
		}

		return $return;
	}

	//执行主程
	public function exec($path, $content = '', $view){
		$view->set('FEATHER_STATIC_DOMAIN', $this->domain);

		$path = '/' . ltrim($path, '/');
		$cache = null;

		if($this->cache_dir){
			$md5path = rtrim($this->cache_dir, '/') . '/' . md5($path) . '.php';

			if(is_file($md5path)){
				$cache = @require($md5path);
			}
		}

		if(!$cache){
			$this->initMap();

			//拿到当前文件所有的map信息
			$selfMap = $this->getResources($path);

			if(!isset($selfMap['isPagelet'])){
				$selfMap = array_merge_recursive($this->commonMap, $selfMap);
			}

			$headJsInline = array();

			if(isset($selfMap['deps'])){
				$config = $this->getRequireMD($selfMap['deps']);
				$config['domain'] = $this->domain;
				$headJsInline[] = 'require.mergeConfig(' . self::jsonEncode($config) . ')';
			}
		
			$cache = array(
				'FEATHER_USE_HEAD_SCRIPTS' => array(
					'inline' => $headJsInline
				),
		        'FEATHER_USE_SCRIPTS' => array(),
				'FEATHER_USE_STYLES' => array()
			);

			if(isset($selfMap['headJs'])){
				$cache['FEATHER_USE_HEAD_SCRIPTS']['outline'] = $this->getUrl($selfMap['headJs']);
			}

			if(isset($selfMap['bottomJs'])){
				$cache['FEATHER_USE_SCRIPTS']['outline'] = $this->getUrl($selfMap['bottomJs']);
			}

			if(isset($selfMap['css'])){
				$cache['FEATHER_USE_STYLES']['outline'] = $this->getUrl($selfMap['css']);
			}

			//如果需要设置缓存
		    if($this->cache_dir){
		   		$output = var_export($cache, true);
		    	$date = date('Y-m-d H:i:s');
		    	file_put_contents($md5path, "<?php\r\n/*\r\ndate: {$date}\r\nfile: {$path}\r\n*/return {$output};");
		    }
		}

		//设置模版值
		$view->set($cache);

		return $content;
	}

	private static function jsonEncode($v){
    	return str_replace('\\', '', json_encode($v));
	}
}