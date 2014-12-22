<?php
/*
自动加载动态资源插件
*/
class Feather_View_Plugin_Autoload_Static extends Feather_View_Plugin{
	//获取页面所有的静态资源
	protected function getResources($path, $maps){
		$selfMap = isset($maps[$path]) ? $maps[$path] : array();

		if(isset($selfMap['components'])){
			$componentsMap = array();

			foreach($selfMap['components'] as $components){
				$componentsMap = array_merge_recursive($componentsMap, $this->getResources($components, $maps));
			}

			return array_merge_recursive($componentsMap, $selfMap);
		}

		return $selfMap;
	}

	//获取静态资源正确的url
	protected function getUrl($resources, $maps, $domain = ''){
		$tmp = array();

		foreach($resources as $v){
			if(isset($maps[$v])){
				$info = $maps[$v];

				if(isset($info['deps'])){
					$tmp = array_merge($tmp, $this->getUrl($info['deps'], $maps, $domain));
				}

				$tmp[] = $domain . $info['url'];
			}else{
				$tmp[] = $v;
			}
		}

		return array_unique($tmp);
	}

	//获取require中的所有map信息和deps信息
	protected function getRequireMD($deps, $maps){
		$mapResult = array(); 
		$depResult = array();
		$tmpDeps = array();

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
		$view->set('FEATHER_STATIC_DOMAIN', $domain = $this->getOption('domain'));

		$cache = null;
		$path = '/' . ltrim($path, '/');

		if($cache_dir = $this->getOption('cache_dir')){
			$md5path = rtrim($cache_dir, '/') . '/' . md5($path) . '.php';

			if(is_file($md5path)){
				$cache = @require($md5path);
			}
		}

		if(!$cache){
			//如果没有缓存
			$array = array();

			//合并map表
			foreach((array)$this->getOption('resources') as $resource){
				$resource = require($resource);
				$array = array_merge_recursive($array, $resource);
			}

			$maps = $array['map'];

			//拿到当前文件所有的map信息
			$selfMap = $this->getResources($path, $maps);

			if(!isset($selfMap['isPagelet'])){
				$selfMap = array_merge_recursive($array['commonMap'], $selfMap);
			}

			$headJsInline = array('require.config=' . $array['requireConfig']);

			if(isset($selfMap['deps'])){
				$config = $this->getRequireMD($selfMap['deps'], $maps);
				$config['domain'] = $domain ? $domain : '';
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
				$cache['FEATHER_USE_HEAD_SCRIPTS']['outline'] = $this->getUrl($selfMap['headJs'], $maps, $domain);
			}

			if(isset($selfMap['bottomJs'])){
				$cache['FEATHER_USE_SCRIPTS']['outline'] = $this->getUrl($selfMap['bottomJs'], $maps, $domain);
			}

			if(isset($selfMap['css'])){
				$cache['FEATHER_USE_STYLES']['outline'] = $this->getUrl($selfMap['css'], $maps, $domain);
			}

		    //如果需要设置缓存
		    if(isset($opt['cache_dir'])){
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