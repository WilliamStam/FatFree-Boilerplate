<?php
/*
 * Date: 2011/04/15
 * Time: 8:48 AM
 */

class user {
	const usertable = "ss_users";
	private static $username = "";
	private static $password = "";
	private static $output = "";
	private static $uID = "";
	private static $userstr = array("ID"=>"","email"=>"","uID"=>"", "validated" => "");

	public static function init() {
		$uID = self::uID();
		return self::output($uID);
	}
	public static function details($fld=""){
		$uID = self::uID();
		if ($uID){
			if ($fld){
				return (isset(self::$userstr[$fld]))? self::$userstr[$fld] : false;
			} else {
				return self::$userstr;
			}

		} else return false;
	}
	public static function login($username = "", $password = "", $redirectIn = "/", $redirectOut = "/") {
		$username = ($username) ? mysql_real_escape_string(F3::scrub($username)) : "";
		$password = ($password) ? mysql_real_escape_string(F3::scrub($password)) : "";

		if ($username) {
			Cookie::Set("username", $username, cookie::ThirtyDays);
		}
		$axon = new Axon(self::usertable);
		$axon->load('email="' . $username . '" AND password = "' . $password . '"');
		if ($axon->dry()) {
			header("location : $redirectOut");
		} else {
			$_SESSION['uid'] = $axon->ID;
			$axon->lastLogin = date("Y-m-d H:i:s"); // 2011-04-18 12:29:49
			$axon->save();
			header("location : $redirectIn");
		}
	}

	public static function logout() {
		if (!isset($_SESSION)) {
			session_start();
		}

		$_SESSION['uid'] = NULL;
		unset($_SESSION['uid']);
		session_destroy();
		header("location : /");
	}

	public static function output($uID = '') {
		$t = "";
		$msg = (isset($_GET['msg'])) ? $_GET['msg'] : '';
		if ($uID) {
			ob_start();
			$tmpl = new template("_loggedin.html");
			$tmpl->name = self::$userstr['email'];
			$tmpl->validated = self::$userstr['validated'];
			$tmpl->msg = $msg;
			echo $tmpl->load();

			$t = ob_get_contents();
			ob_end_clean();
		} else {

			ob_start();
			$tmpl = new template("_login.html");
			$tmpl->username = Cookie::Get("username");
			$tmpl->msg = $msg;
			echo $tmpl->load();
			$t = ob_get_contents();
			ob_end_clean();
		}

		return $t;
	}

	public static function uID() {
		if (isset($_SESSION['uid'])) {
			$uID = $_SESSION['uid'];
			$axon = new Axon(self::usertable);
			$axon->load('ID="' . $uID . '"');
			if ($axon->dry()) {
				return false;
			} else {
				self::$uID = $axon->ID;
				foreach (self::$userstr as $key => $value){
					self::$userstr[$key] = $axon->$key;
				}

				return self::$uID;

			}

		} else return false;
	}

	public static function access() {
		return (self::uID()) ? true : false;
	}
}
