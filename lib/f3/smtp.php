<?php

/**
	SMTP plugin for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2010 F3::Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package Email
		@version 2.0.0
**/

//! SMTP plugin
class SMTP extends Base {

	//@{ Locale-specific error/exception messages
	const
		TEXT_MailHeader='%s: header is required',
		TEXT_MailBlank='Message must not be blank';
	//@}

	private
		//@{ Message properties
		$headers;
		//@}

	/**
		Fix header
			@param $key
			@private
	**/
	private function fixheader($key) {
		return str_replace(' ','-',
			ucwords(str_replace('-',' ',self::subst($key))));
	}

	/**
		Bind value to e-mail header
			@param $key string
			@param $val string
			@public
	**/
	function set($key,$val) {
		$key=$this->fixheader($key);
		$this->headers[$key]=self::subst($val);
	}

	/**
		Return value of e-mail header
			@param $key string
			@public
	**/
	function get($key) {
		$key=$this->fixheader($key);
		return isset($this->headers[$key])?$this->headers[$key]:NULL;
	}

	/**
		Remove header
			@param $key
			@public
	**/
	function clear($key) {
		$key=$this->fixheader($key);
		unset($this->headers[$key]);
	}

	/**
		Transmit message
			@param $message string
			@public
	**/
	function send($message) {
		// Required headers
		$reqd=explode('|','To|Subject');
		// Retrieve headers
		$headers=$this->headers;
		foreach ($reqd as $id)
			if (!isset($headers[$id])) {
				trigger_error(sprintf(self::TEXT_MailHeader,$id));
				return;
			}
		// Message should not be blank
		$message=self::subst($message);
		if (!$message) {
			trigger_error(self::TEXT_MailBlank);
			return;
		}
		$str='';
		// Stringify headers
		foreach ($headers as $key=>$val)
			if (!in_array($key,$reqd))
				$str.=$key.': '.$val."\r\n";
		// Send
		return mail(
			$headers['To'],$headers['Subject'],wordwrap($message,70),$str
		);
	}

	/**
		Class constructor
			@public
	**/
	function __construct() {
		$this->headers=array(
			'MIME-Version'=>'1.0',
			'Content-Type'=>'text/plain; charset='.self::ref('ENCODING')
		);
	}

}
