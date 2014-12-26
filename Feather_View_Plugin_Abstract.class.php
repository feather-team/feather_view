<?php
abstract class Feather_View_Plugin_Abstract{
	protected $options = array();

	public function __construct($opt = array()){
		$this->options = (array)$opt;
		$this->initialize();
	}

	protected function initialize(){}

	public function getOption($name = null){
		return isset($this->options[$name]) ? $this->options[$name] : null;
	}

	public function setOption($name, $value = null){
		$this->options[$name] = $value;
	}

	abstract public function exec($path, $content, $view);
}