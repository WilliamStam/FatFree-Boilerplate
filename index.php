<?php

session_start();

$app = require(__DIR__ . '/lib/base.php');
require_once('lib/Haanga.php');

function isLocal() {
	if (file_exists("D:/web/local.txt")) {
		return true;
	} else return false;
}

F3::config('inc/config.ini');
$app->set('version', '0.1');

$app->set('AUTOLOAD', 'lib/|inc/|controllers/|models/');
$app->set('PLUGINS', 'lib/f3/');
$app->set('CACHE', TRUE);
$app->set('DEBUG', 2);
$app->set('EXTEND', TRUE);
$app->set('GUI', 'gui/');
$app->set('TEMP', 'tmp/');

$app->set('DB', new DB('mysql:host=localhost;dbname=dbname', 'dbusername', 'dbpassword'));


$app->route('GET /inc/config.ini',function(){
	echo "fuck off";
});


$app->route('GET /', 'Main->index');
$app->route('GET /about', 'Main->about');
$app->route('GET /contact', 'Main->contact');


$app->route('GET /photo/@folder/@w/@h/@filename', function(){
	$folder = F3::get('PARAMS["folder"]');
	$w = F3::get('PARAMS["w"]');
	$h = F3::get('PARAMS["h"]');
	$filename = F3::get('PARAMS["filename"]');
	$file = getcwd() . DIRECTORY_SEPARATOR . "media" . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $filename;
	Graphics::thumb($file,$w,$h);
});


$app->run();


class template {
	private $config = array(), $vars = array();

	function __construct($template) {
		$this->config['cache_dir'] = F3::get('TEMP');
		$this->config['template_dir'] = F3::get('GUI');

		Haanga::Configure($this->config);
		$this->template = $template;

	}

	public function __get($name) {
		return $this->vars[$name];
	}

	public function __set($name, $value) {
		$this->vars[$name] = $value;
	}

	public function load() {
		// all pages get these
		$this->vars['version'] = F3::get('version');

		if (( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ) && (isset($this->vars['page']))) {
			$this->template = $this->vars['page'];
			$result = Haanga::load($this->template, $this->vars);
			if (isset($this->vars['js'])) {
				$result = $result . '<script src="/gui/js/' . $this->vars['js'] . '?v=' . $this->vars['version'] . '></script>';
			}
		} else {
			$result = Haanga::load($this->template, $this->vars);
		}
		return $result;

	}
}




?>
