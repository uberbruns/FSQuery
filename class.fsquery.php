<?php

/*

The MIT License
===============

Copyright (c) 2012 Karsten Bruns (karsten@bruns.me)

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.



Examples
========

## MIME Type
$query = $fs_query->query("image/jpeg");

## FILE- OR FOLDERNAME
$query = $fs_query->query("#PDF");

## EXTENSION
$query = $fs_query->query(".gif");

## DESCENDANTS
$query = $fs_query->query("#websites #index.html");

## CHILD
$query = $fs_query->query("#websites > directory > #index.html");

## ROOT ELEMENTS
$fs_query = new FSQuery("/Users/kb/Desktop/Portfolio");
$query = $fs_query->query("#Portfolio > directory");

## COMBINED QUERIES
$query = $fs_query->query("#Portfolio > directory, #websites #index.html");


(TIP)

Don't let this script run hundred times in a second or on every
page request. Get your data from the filesystem, process it and
cache the result. This script is not optimized for speed and/or
efficiency, but for convenience.


*/


define("FSQ_SELECTOR_KIND_UNKNOWN", 0); // "a"
define("FSQ_SELECTOR_KIND_TYPE", 1); // "a"
define("FSQ_SELECTOR_KIND_MIME", 2); // "a/a"
define("FSQ_SELECTOR_KIND_BASENAME", 3); // "#a"
define("FSQ_SELECTOR_KIND_FILENAME", 4); // "#a.a"
define("FSQ_SELECTOR_KIND_EXT", 5); // ".a"
define("FSQ_SELECTOR_KIND_ANY", 6); // "*"

define("FSQ_SELECTOR_COMBINATOR_UNKNOWN", 0);
define("FSQ_SELECTOR_COMBINATOR_DESCENDANT", 1); // " "
define("FSQ_SELECTOR_COMBINATOR_CHILD", 2); // " > "

define("FSQ_ATTRIBUTE_TEST_NONE", 0);
define("FSQ_ATTRIBUTE_TEST_EQUALS", 1); // "*[a=b]"
define("FSQ_ATTRIBUTE_TEST_STARTS", 2); // "*[a^=b]"
define("FSQ_ATTRIBUTE_TEST_END", 3); // "*[a$=b]"
define("FSQ_ATTRIBUTE_TEST_CONTAINS", 4); // "*[a*=b]"

class FSQuery {


	public $path;
	public $base_url;
	public $scan_results;
 	public $case_sensitive;

 	protected $sort_key;


	public function __construct($path, $url = '/') {

		$this->path = rtrim(realpath($path), DIRECTORY_SEPARATOR);
		$this->scan_results = array();
		$this->strto_sensitive = false;
		$this->base_url = $url;
		$this->sort_key = "path";
	
	}



	public function query($queries, $parent = 0) {

		$queries = trim($queries);
		if (!$queries) return;

		if (!(count($this->scan_results) > 0)) {
			$this->scan_path();
		}


		$results = array();

		foreach (explode(",", $queries) as $query) {

			$query = trim($query);

			$all_query_elements = explode(" ", $query);
			$number_of_query_elements = count($all_query_elements);
			$scope = $this->scan_results;
			$valid_paths = array(($parent) ? $parent["path"] : $this->path);
			$selector_combinator = FSQ_SELECTOR_COMBINATOR_DESCENDANT;

			foreach ($all_query_elements as $i => $query_element) {

				$query_element_parts = explode("[", $query_element);
				$query_element = $this->strto($query_element_parts[0]);
				$selector_kind = FSQ_SELECTOR_KIND_UNKNOWN;
				$next_selector_combinator = FSQ_SELECTOR_COMBINATOR_DESCENDANT;
				$attribute_test = (isset($query_element_parts[1])) ? substr($query_element_parts[1], 0, -1) : false;


				if (strpos($query_element, "/") > 0) {
					$selector_kind = FSQ_SELECTOR_KIND_MIME;
				} elseif (substr($query_element, 0, 1) == ">") {
					$next_selector_combinator = FSQ_SELECTOR_COMBINATOR_CHILD;
				} elseif (substr($query_element, 0, 1) == ".") {
					$selector_kind = FSQ_SELECTOR_KIND_EXT;
					$query_element = substr($query_element, 1);
				} elseif (substr($query_element, 0, 1) == "#" && (strpos($query_element, ".") > 0)) {
					$selector_kind = FSQ_SELECTOR_KIND_FILENAME;
					$query_element = substr($query_element, 1);
				} elseif (substr($query_element, 0, 1) == "#") {
					$selector_kind = FSQ_SELECTOR_KIND_BASENAME;
					$query_element = substr($query_element, 1);
				} elseif ($query_element == "*") {
					$selector_kind = FSQ_SELECTOR_KIND_ANY;
				} else {
					$selector_kind = FSQ_SELECTOR_KIND_TYPE;
				}


				if ($selector_kind) {

					$new_valid_paths = array();

					foreach ($scope as $item) {

						$is_valid_path = false;

						foreach ($valid_paths as $valid_path) {

							if (($selector_combinator == FSQ_SELECTOR_COMBINATOR_DESCENDANT && substr($item["path"], 0, strlen($valid_path)) == $valid_path) ||
								($selector_combinator == FSQ_SELECTOR_COMBINATOR_CHILD && substr($item["path"], 0, strlen($item["path"])-strlen(basename($item["path"]))) == $valid_path)) {
								$is_valid_path = true;
								break;
							}
							
						}


						if ($is_valid_path) {
			
							if ((isset($item["type"]) && $selector_kind == FSQ_SELECTOR_KIND_TYPE && $this->strto($item["type"]) == $query_element) ||
							    (isset($item["basename"]) && $selector_kind == FSQ_SELECTOR_KIND_BASENAME && $this->strto($item["basename"]) == $query_element) ||
							    (isset($item["filename"]) && $selector_kind == FSQ_SELECTOR_KIND_FILENAME && $this->strto($item["filename"]) == $query_element) ||
							    (isset($item["extension"]) && $selector_kind == FSQ_SELECTOR_KIND_EXT && $this->strto($item["extension"]) == $query_element) ||
								(isset($item["mime"]) && $selector_kind == FSQ_SELECTOR_KIND_MIME && $this->strto($item["mime"]) == $query_element) ||
								($selector_kind == FSQ_SELECTOR_KIND_ANY)) {

								if (!$attribute_test || $this->validate($attribute_test, $item) ) {

									if ($i == $number_of_query_elements-1) {
										$results[] = $item;
									} elseif ($item["is_dir"]) {
										$add = $item["path"]."/";
										if (!in_array($add, $new_valid_paths)) $new_valid_paths[] = $add;
									}

								}

							}
			
						}

					}

					$valid_paths = $new_valid_paths;

				}

				$selector_combinator = $next_selector_combinator;

			}

		}

		$this->sort($results);
		return $results;

	}

	
	public function sort($items, $key="path") {

		$key = trim($key);

		$this->sort_key = $key;
		uasort($items, array($this, 'sort_items'));
		$this->sort_key = "path";

		return $items;

	}



	public function rsort($items, $key="path") {

		return array_reverse($this->sort($items, $key));

	}



	private function sort_items($a, $b) {

		$keys = explode(".", $this->sort_key);

		foreach ($keys as $key) {
			$key = trim($key);
			if(isset($a[$key]) && isset($b[$key])) {
				$a = $a[$key];
				$b = $b[$key];
			} else {
				break;
			}
		}

	    return strcasecmp($a, $b);

	}
 


	protected function scan_path() {

		$new_scan_results = array($this->scan_item($this->path));

		$all_items = array($this->path);
 
		for ($i = 0; $i < count($all_items); $i++) {

			if (is_dir($all_items[$i])) {

				$new_items = glob($all_items[$i] . '/*');
				natcasesort($new_items);

				$all_items = array_merge($all_items, $new_items);

				foreach ($new_items as $item_path) {
					
					$new_scan_results[] = $this->scan_item($item_path);

				}

			}
		
		}

		$this->scan_results = $new_scan_results;

	}



	protected function scan_item($item_path) {

		$is_dir = is_dir($item_path);
		$path_parts = pathinfo($item_path);
		$file_info_resource = finfo_open(FILEINFO_MIME_TYPE);
		$mime_type = finfo_file($file_info_resource, $item_path);
		$mime_type_array = explode("/", $mime_type);
		$root_path = substr($this->path, 0, strlen($this->path)-strlen(basename($this->path)));
		$stats = stat($item_path);


		$scanned_item = array();
		$scanned_item["path"] = $item_path;
		$scanned_item["is_dir"] = $is_dir;
		$scanned_item["hierarchy"] = explode("/", substr($item_path, strlen($root_path)));
		$scanned_item["mime"] = $mime_type;
		$scanned_item["type"] = $mime_type_array[0];
		$scanned_item["subtype"] = (isset($mime_type_array[1])) ? $mime_type_array[1] : false;
		$scanned_item["basename"] = $path_parts['filename'];
		$scanned_item["namecomp"] = $this->components($path_parts['filename']);
		$scanned_item["filename"] = $path_parts['basename'];
		$scanned_item["info"]["mtime"] = $stats["mtime"];
		$scanned_item["info"]["ctime"] = $stats["ctime"];
		$scanned_item["info"]["size"]  = $stats["size"];
		$scanned_item["url"] = $this->base_url.substr($item_path, strlen($root_path)+strlen(basename($this->path)));


		if (isset($path_parts['extension'])) {
			$scanned_item["extension"] = $path_parts['extension'];
		}


		if ($mime_type_array[0] == "image") {
			list($width, $height, $type, $attr) = getimagesize($item_path);
			$scanned_item["info"]["width"] = $width;
			$scanned_item["info"]["height"] = $height;
		}


		if (!$is_dir) {
			array_pop($scanned_item["hierarchy"]);
		}


		foreach ($scanned_item["hierarchy"] as $key => $value) {
			$scanned_item["hierarchycomp"][$key] = $this->components($value);
		}


		$scanned_item["r"]["hierarchy"] = array_reverse($scanned_item["hierarchy"]);
		$scanned_item["r"]["hierarchycomp"] = array_reverse($scanned_item["hierarchycomp"]);
		$scanned_item["r"]["namecomp"] = array_reverse($scanned_item["namecomp"]);


		finfo_close($file_info_resource);
		ksort($scanned_item);
		return $scanned_item;

	}



	protected function validate($attribute_test, $item) {

		$operator = false;
		$operators = array("^=","$=","*=","=");

		foreach ($operators as $operator_candidate) {
			if (strpos($attribute_test, $operator_candidate) !== false) {
				$operator = $operator_candidate;
				break;	
			}
		}

		if ($operator) {

			list($keys, $needle) = explode($operator, $attribute_test);
			$haystack = $item;
			$needle = trim($needle);

			foreach (explode(".", $keys) as $key) {
				$key = trim($key);
				if(isset($haystack[$key])) {
					$haystack = $haystack[$key];
				} else {
					break;
				}
			}

			$haystack = trim($haystack);
			if (($operator == "=" && $needle == $haystack) ||
				($operator == "*=" && strpos($haystack, $needle) !== 0) ||
				($operator == "^=" && substr($haystack, 0, strlen($needle)) === $needle) ||
				($operator == "$=" && substr($haystack, -strlen($needle)) === $needle)) {
				return true;
			}

		}

		return false;

	}



	protected function strto($str) {

		$str = str_replace(" ","",$str);
		return ($this->strto_sensitive) ? $str : strtolower($str);
	
	}



	protected function components($str) {

		return explode("_", str_replace(array(".","-","@","+"," "), array("_","_","_","_","_"), $str));
	
	}


 
}


 

?>