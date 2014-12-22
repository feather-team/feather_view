<?php
/*
插件接口类
提供基础方法，以及接口约定
*/
abstract class Feather_View_Plugin{
	protected $options = array();

	public function __construct($opt = array()){
		$this->options = (array)$opt;
	}

	public function getOption($name = null){
		return isset($this->options[$name]) ? $this->options[$name] : null;
	}

	public function setOption($name, $value = null){
		$this->options[$name] = $value;
	}

	abstract public function exec($path, $content, $view);
}