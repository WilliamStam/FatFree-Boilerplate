<?php

class Main {

	function index() {
		$tmpl = new template("template.html");
		$tmpl->page = "index.html";
		$tmpl->js = "page_index.js";
		$tmpl->heading = "HOME";
		$tmpl->text = "yo ho.. <h1>whats happening?</h1> notice how the template is text|safe. its so that html is rendered";
		$tmplOutput = $tmpl->load();
		echo $tmplOutput;
	}

	function about() {
		$tmpl = new template("template.html");
		$tmpl->page = "about.html";
		$tmpl->js = "page_about.js";
		$tmpl->heading = "About";
		$tmpl->text = "hmm about text stuff";
		$tmplOutput = $tmpl->load();
		echo $tmplOutput;
	}

	function contact() {
		$tmpl = new template("template.html");
		$tmpl->page = "contact.html";
		$tmpl->heading = "Contact";
		$tmpl->text = "if you havent figured it out.. this is how you set tmpl variables";
		$tmplOutput = $tmpl->load();
		echo $tmplOutput;
	}


}
