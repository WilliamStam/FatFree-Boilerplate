<?php
/*
 * Date: 2011/04/27
 * Time: 9:48 AM
 */

class register {

	public static function init() {
		$name = (isset($_POST["name"])) ? $_POST["name"] : "";
		$email = (isset($_POST["email"])) ? $_POST["email"] : "";
		$password = (isset($_POST["password"])) ? $_POST["password"] : "";
		$securityQ = (isset($_POST["measure"])) ? $_POST["measure"] : "";

		if (!$_POST) {
			return self::register_form();
		} else {

			$correct = true;
			if ($name) {
				$correct = false;
			}


			if ($email) {
				if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$email = strtolower($email);
					$axon = new axon("ss_users");
					$axon->load("email = '$email'");
					if (!$axon->dry()) {
						$emailmsg = '<span class="error">Email Exists</span> ';
						$correct = false;
					} else {
						$emailmsg = "<span class='ok'>Ok</span>";
					}
				} else {
					$emailmsg = '<span class="error">Not a valid email</span> ';
					$correct = false;
				}

			} else {
				$emailmsg = '<span class="info">Required</span> ';
				$correct = false;
			}


			if ($password) {
				if (strlen($password) < 6) {
					$passwordmsg = '<span class="error">Too short (min 6 chars)</span>';
					$correct = false;
				} else $passwordmsg = "<span class='ok'>Ok</span>";


			} else {
				$passwordmsg = '<span class="info">Required (min 6 chars)</span> ';
				$correct = false;
			}

			$sec = ($email) ? substr($email, 1, 1) : "";
			if ($securityQ) {
				if ($securityQ != $sec) {
					$securityQmsg = '<span class="error">Doesnt Match</span>';
					$correct = false;
				} else $securityQmsg = "<span class='ok'>Ok</span>";


			} else {
				$securityQmsg = '<span class="error">Required</span>';
				$correct = false;
			}


			if ($correct) {
				return self::doregister($email, $password);
			} else {
				return self::register_form($name, $email, $password, $securityQ, $emailmsg, $passwordmsg, $securityQmsg);
			}


		}


	}

	private static function doregister($email, $password) {
		$axon = new axon("ss_users");
		$axon->email = $email;
		$axon->password = $password;

		$axon->save();
		$axon->load("ID = '" . $axon->_id . "'");

		$validate = md5($axon->dateRegisterd);

		self::validate($axon->ID);

		Cookie::Set("username", $axon->email, cookie::ThirtyDays);
		$_SESSION['uid'] = $axon->ID;

		return self::register_form_success($email);
	}


	public static function register_form($name = "", $email = "", $password = "", $securityQ = "", $emailmsg = "", $passwordmsg = "", $securityQmsg = "") {

		$emailmsg = (!$emailmsg) ? '<span class="info">Required</span>' : $emailmsg;
		$passwordmsg = (!$passwordmsg) ? '<span class="info">Required</span>' : $passwordmsg;

		$tmpl = new template("template.html");
		$tmpl->page = "register.html";
		$tmpl->js = "page_register.js";

		$tmpl->name = $name;
		$tmpl->email = $email;
		$tmpl->emailmsg = $emailmsg;
		$tmpl->password = $password;
		$tmpl->passwordmsg = $passwordmsg;
		$tmpl->security = $securityQ;
		$tmpl->securitymsg = $securityQmsg;

		$tmpl->heading = "Register to use Subscriptions";

		$tmplOutput = $tmpl->load();
		return $tmplOutput;
	}

	public static function register_form_success($email) {
		$tmpl = new template("template.html");
		$tmpl->page = "register_success.html";
		$tmpl->heading = "Register to use Subscriptions";
		$tmpl->email = $email;
		$tmplOutput = $tmpl->load();
		return $tmplOutput;
	}

	public static function loggedin() {
		$tmpl = new template("template.html");
		$tmpl->page = "register_alreadyin.html";
		$tmpl->heading = "Register to use Subscriptions";

		$tmplOutput = $tmpl->load();
		return $tmplOutput;
	}

	public static function validate($uID = '', $redirect = "") {
		$axon = new axon("ss_users");
		if (isset($_GET["validate"])) {
			$axon->load("md5(dateRegisterd) = '" . $_GET["validate"] . "'");
			if (!$axon->dry()){
				$axon->validated = "1";
				$axon->save();
			}

			header("location : /");

		} else {
			if ($uID) {

				$axon->load("ID = '" . $uID . "'");
				if ($axon->validated != "1"){

					$fromE = F3::get("EMAIL");

					$validatecode = md5($axon->dateRegisterd);

					$body = '<a href="'. $_SERVER['HTTP_HOST'] .'/register/validate?validate='.$validatecode.'">' . $_SERVER['HTTP_HOST'] . '/register/validate?validate=' . $validatecode . '</a> ';

					$mail = new SMTP;
					$mail->set('from', '<' . $fromE . '>');
					$mail->set('to', '<' . $axon->email . '>' );
					$mail->set('subject', 'Welcome - Validate your email address');
					$mail->send($body);

					if ($redirect){
						header("location : $redirect");
					}

				}


			} else {
				header("location : /");
			}
		}

	}


}
