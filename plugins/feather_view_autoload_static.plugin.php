<?php
/*
自动加载静态资源插件
opt: array(
	'resources' => array(path1, path2),
	'domain' => '静态资源的domain值',
	'cache_dir' => 'cache目录'
)
*/
function feather_view_autoload_static($path, $content = '', $view, $opt = array()){
	$root = '';
	$cache = null;

	if(isset($opt['domain'])){
		$root = $opt['domain'];
	}

	$view->set('FEATHER_STATIC_DOMAIN', $root);
	
	$path = '/' . ltrim($path, '/');

	if(isset($opt['cache_dir'])){
		$md5path = rtrim($opt['cache_dir'], '/') . '/' . md5($path) . '.php';

		if(is_file($md5path)){
			$cache = @require($md5path);
		}
	}

	if(!$cache){
		//如果没有缓存
		$array = array();

		//合并map表
		foreach($opt['resources'] as $resource){
			$resource = require($resource);
			$array = array_merge_recursive($array, $resource);
		}

		$maps = $array['map'];

		//拿到当前文件所有的map信息
		$self_map = _feather_view_autoload_static_get_resources($path, $maps);

		if(!isset($self_map['isPagelet'])){
			$self_map = array_merge_recursive($array['commonMap'], $self_map);
		}

		//获取文件的loadjs的map和deps信息
		$map_deps = isset($self_map['deps']) ? _feather_view_autoload_static_get_require_md($self_map['deps'], $maps) : array();
		$headJs = isset($self_map['headJs']) ? _feather_view_autoload_static_get_url($self_map['headJs'], $maps, $root) : array();
		$bottomJs = isset($self_map['bottomJs']) ? _feather_view_autoload_static_get_url($self_map['bottomJs'], $maps, $root) : array();
		$css = isset($self_map['css']) ? _feather_view_autoload_static_get_url($self_map['css'], $maps, $root) : array();

		$cache = array(
			'FEATHER_USE_HEAD_SCRIPTS' => array(
				'outline' => $headJs,
				'inline' => array(
		        	//featherjs配置
		            'require.config=' . $array['requireConfig'], 
		            'require.mergeConfig(' . _feather_view_autoload_static_json_encode($map_deps) . ')'
		        )
			),
	        'FEATHER_USE_SCRIPTS' => array(
	        	'outline' => $bottomJs
	        ),
			'FEATHER_USE_STYLES' => array(
				'outline' => $css
			)	        
		);

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

//获取所有的静态资源
function _feather_view_autoload_static_get_resources($path, $maps){
	if(isset($maps[$path])){
		$self_map = $maps[$path];
	}else{
		$self_map = array();
	}

	if(isset($self_map['components'])){
		$components_map = array();

		foreach($self_map['components'] as $components){
			$components_map = array_merge_recursive($components_map, _feather_view_autoload_static_get_resources($components, $maps));
		}

		return array_merge_recursive($components_map, $self_map);
	}

	return $self_map;
}

//获取静态资源正确的url
function _feather_view_autoload_static_get_url($resources, $maps, $domain = ''){
	$tmp = array();

	foreach($resources as $v){
		if(isset($maps[$v])){
			$info = $maps[$v];

			if(isset($info['deps'])){
				$tmp = array_merge($tmp, _feather_view_autoload_static_get_url($info['deps'], $maps, $domain));
			}

			$tmp[] = $domain . $info['url'];
		}else{
			$tmp[] = $v;
		}
	}

	return array_unique($tmp);
}

//获取loadjs的map和deps配置
function _feather_view_autoload_static_get_require_md($deps, $maps){
	$map_result = array(); 
	$dep_result = array();
	$tmp_deps = array();

	foreach($deps as $m){
		if(isset($maps[$m])){
			$v = $maps[$m];

			$url = $v['url'];

			if(!isset($map_result[$url])){
				$map_result[$url] = array();
			}

			$map_result[$url][] = $m;

			//deps
			if(isset($v['deps'])){
				$deps = $v['deps'];

				if(isset($v['isMod'])){
					$dep_result[$m] = $deps;
				}

				$tmp_deps = array_merge($tmp_deps, $deps);
			}
		}
	}

	$return = array('map' => $map_result, 'deps' => $dep_result);

	if(count($tmp_deps) > 0){
		$return = array_merge_recursive($return, _feather_view_autoload_static_get_require_md($tmp_deps, $maps));
	}

	foreach($return['map'] as $key => $value){
		$return['map'][$key] = array_values(array_unique($value));
	}

	foreach($return['deps'] as $key => $value){
		$return['deps'][$key] = array_values(array_unique($value));
	}

	return $return;
}

//php json encode会将所有引号转义，此处不需要转义
function _feather_view_autoload_static_json_encode($v){
    return str_replace('\\', '', json_encode($v));
}
