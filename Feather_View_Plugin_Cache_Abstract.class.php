<?php
abstract class Feather_View_Plugin_Cache_Abstract{
	abstract public function write($path, $content = null);
	abstract public function read($path);
}