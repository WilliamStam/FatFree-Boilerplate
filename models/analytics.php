<?php
/*
 * Date: 2011/04/19
 * Time: 9:57 AM
 *
 * -------------- USAGE --------------
 * $ga = new analytics();
 * echo $ga->visitorType
 *
 * -------------- RESULT --------------
 * returns a json string of the report
 *
 * -------------- AVAILABLE REPORTS --------------
 * browsers, visitorType, dailyVisits, locationContinent, locationCountry, trafficSources
 */

class analytics {

	public $startDate = "";
	public $endDate = "";
	public $display = "";
	private $metricsSimple = "";
	private $metricsAdvanced = "";
	private $ga = "";

	private $ga_username = "";
	private $ga_password = "";
	private $ga_profile = "";


	function __construct($startDate = "", $endDate = "") {
		// if start date and end date are empty.. go back 30 days
		$this->startDate = ($startDate) ? date("Y-m-d", strtotime($startDate)) : date("Y-m-d", strtotime('-31 days'));
		$this->endDate = ($endDate) ? date("Y-m-d", strtotime($endDate)) : date("Y-m-d", strtotime('-1 days'));

		// setting up the various metrics
		$this->metricsSimple = array("visits", "pageviews");
		$this->metricsAdvanced = array("visits", "pageviews", "pageviewsPerVisit", "visitBounceRate", "entranceBounceRate", "timeOnSite", "avgTimeOnSite", "uniquePageviews");

		// logging into google analytics
		$this->ga = new gapi(F3::get("analytics_username"), F3::get("analytics_password"));
		$this->ga_profile = F3::get("analytics_profile");

	}

	public function __get($name) {
		return $this->data($name);
	}


	private function data($name) { // the thing that retrives the data
		$value = trim($name);
		$sections = $this->sections();
		$output = "";
		if (array_key_exists($value, $sections)) {
			$s = $value;
			$d = $sections[$value][0];
			$m = $sections[$value][1];
			$sort = $sections[$value][2];
			$filter = $sections[$value][3];
			$s_value = $sections[$value];

			$startDate = $sections[$value][4];
			$endDate = $sections[$value][5];

			$this->ga->requestReportData($this->ga_profile, $d, $m, $sort, $filter, $startDate, $endDate, 1, 35);
			$results = $this->ga->getResults();

			$output[$s] = array();


			foreach ($results as $name => $value) {
				$resultvalue = trim($value);
				$resultname = $name;
				$output[$s][$resultname]["label"] = $resultvalue;

				foreach ($m as $metric) {

					$met = "get" . $metric;
					$met = $value->$met();
					$output[$s][$resultname][$metric] = $met;

				}

				if (array_key_exists("sub", $s_value)) {
					$sub = $s_value["sub"];

					$sub_d = $sub[0];
					$sub_m = $sub[1];
					$sub_sort = $sub[2];
					$sub_filter = $sub[3];

					if ($sub_filter) {

						preg_match_all("/{[^}]*}/", $sub_filter, $matches);

						foreach ($matches[0] as $name => $value) {

							$val = str_replace("{", "", $value);
							$val = str_replace("}", "", $val);
							$sub_filter = str_replace("{" . $val . "}", "'" . $resultvalue . "'", $sub_filter);

						}

						//$sub_filter = "country == 'South Africa'";
					}

					$this->ga->requestReportData($this->ga_profile, $sub_d, $sub_m, $sub_sort, $sub_filter, $startDate, $endDate);
					$results = $this->ga->getResults();


					foreach ($results as $name => $value) {
						//$output[$s][$resultname]["sub"][$name] = array();
						$output[$s][$resultname]["sub"][$name]["label"] = trim($value);
						foreach ($sub_m as $metric) {

							$met = "get" . $metric;
							$met = $value->$met();
							$output[$s][$resultname]["sub"][$name][$metric] = trim($met);

						}

					}
				}
			}
		}


		return json_encode($output);

	}


	private function sections() { // setting up the various reports into an array

		/*
		* $sections["reportName"] = array(
		*	array("dimention"),
		*	array("metrics"),
		*	array("sort"),
		*	"filterColum='filterValue'",
		*	"startDate", "endDate"
		* );
		*/

		$sections = array();

		$sections["browsers"] = array(
			array("browser"),
			$this->metricsSimple,
			array("-visits", "-pageviews"),
			"",
			$this->startDate, $this->endDate
		);
		$sections["browsers"]["sub"] = array(
			array("browserVersion"),
			$this->metricsSimple,
			array("browserVersion"),
			"browser=={browser}"
		);

		$sections["visitorType"] = array(
			array("visitorType"),
			$this->metricsAdvanced,
			array("visitorType"),
			"", $this->startDate, $this->endDate
		);


		$sections["dailyVisits"] = array(
			array("date"),
			$this->metricsSimple,
			array("date"),
			"", $this->startDate, $this->endDate
		);

		$sections["locationContinent"] = array(
			array("subContinent"),
			$this->metricsSimple,
			array("-visits"),
			"", $this->startDate, $this->endDate
		);


		$sections["locationCountry"] = array(
			array("country"),
			$this->metricsSimple,
			array("-visits"),
			"'", $this->startDate, $this->endDate

		);
		$sections["locationCountry"]["sub"] = array(
			array("city"),
			$this->metricsSimple,
			array("-visits"),
			"country=={country}"
		);




		$sections["trafficSources"] = array(
			array("medium"),
			$this->metricsSimple,
			array("-visits"),
			"", $this->startDate, $this->endDate
		);
		$sections["trafficSources"]["sub"] = array(
			array("source"),
			$this->metricsSimple,
			array("-visits"),
			"medium=={medium}"
		);


		return $sections;
	}

}
