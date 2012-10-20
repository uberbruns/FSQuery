<?php

	// Classes
	require dirname(__FILE__)."/../class.fsquery.php";
	require dirname(__FILE__)."/Support/Markdown/class.markdown.php";

	// Setup
	$fs_query = new FSQuery(dirname(__FILE__)."/Blog", dirname($_SERVER['PHP_SELF'])."/Blog");
	$css_query = $fs_query->query("#Blog > #Ressources .css");
	$settings_query = $fs_query->query("#Blog > #Settings .ini");
	$blog_settings = parse_ini_file($settings_query[0]["path"], true);

	// Pages
	$pages_query = $fs_query->query("#Blog > #Pages > directory");

	// Posts & Archive
	$archive = array();
	$posts_query = $fs_query->query("#Blog > #Posts > directory > directory");
	foreach ($posts_query as $post_file) {
		$year = $post_file["r"]["hierarchycomp"][0][0];
		$archive[$year] = (isset($archive[$year])) ? ++$archive[$year] : 1;
	}
	krsort($archive);

	// Content
	$content_is_post = false;
	$content_show_archive = false;
	$content_is_page = false;
	$content_is_blog = false;
	$content_posts_per_page = 4;
	$content_posts_pages = 0;

	if (isset($_GET["page"])) {
		$content_query = $fs_query->query("#Blog > #Pages > #".$_GET["page"]);
		$content_is_page = true;
	} elseif (isset($_GET["post"]))  {
		$content_query = $fs_query->query("#Blog > #Posts > * > #".$_GET["post"]);
		$content_is_post = true;
	} elseif (isset($_GET["author"]))  {
		$content_query = $fs_query->query("#Blog > #Posts > #".$_GET["author"]." > directory");
		$content_is_post = true;
	} elseif (isset($_GET["archive"]))  {
		$content_query = $fs_query->query("#Blog > #Posts > directory > directory[r.hierarchy.0^=".$_GET["archive"]."]");
		$content_is_post = true;
		$content_show_archive = true;
	} else {
		$content_query = $posts_query;
		$content_is_post = true;
		$content_show_archive = true;
		$content_is_blog = true;
	}

	if ($content_is_post) {
		$content_query = $fs_query->rsort($content_query,"r.hierarchy.0");
	}

	if ($content_is_blog) {
		$content_query = array_chunk($content_query, $content_posts_per_page);
		$content_posts_pages = count($content_query);
		$content_query = $content_query[0];
	}

?>
<html>
<head>
<title><?=$blog_settings["blog"]["name"]?></title>
<? foreach ($css_query as $css_file) {
	printf("<link rel=\"stylesheet\" type=\"text/css\" href=\"%s\">\n", $css_file['url']);
} ?>
</head>
<body>
<header>
<h1><?=$blog_settings["blog"]["name"]?></h1>
</header>
<nav>
<ul>
<li><a href="blog.php">Home</a></li>
<? foreach ($pages_query as $page) {
	printf("<li><a href=\"?page=%s\">%s</a></li>\n", $page["filename"], $page["basename"]);
} ?>
</ul>
</nav>
<? foreach ($content_query as $i => $content) {
	// Blog Content
	$content_elements = $fs_query->query(".md, image, .html", $content);
	if (count($content_elements) > 0) {
		printf("\n<article>\n");
		foreach ($content_elements as $key => $element) {
			if ($element["extension"] == "md") printf("%s\n", Markdown(file_get_contents($element["path"])));
			elseif ($element["type"] == "image") printf("<img src=\"%s\">\n", $element["url"]);
			elseif ($element["extension"] == "html") printf(file_get_contents($element["path"]));
		}
		if ($content_is_post) {
			$time = mktime(12,0,0,$content["r"]["hierarchycomp"][0][1],$content["r"]["hierarchycomp"][0][2],$content["r"]["hierarchycomp"][0][0]);
			printf("<p>Posted <a href=\"?post=%s\">%s</a> by <a href=\"?author=%s\">%s</a></p>\n", $content["r"]["hierarchy"][0], date("D, d M Y", $time), $content["hierarchy"][2], $content["hierarchy"][2]);
		}
		printf("</article>\n");
		if ($i > 5 && $content_is_blog) break;
	}
}

if ($content_show_archive) {
?>
<nav>
<? if ($content_is_blog ) {
	printf("<a href=\"\">Older</a>\n");
	printf(" | \n");
	printf("<a href=\"\">Newer</a>\n");
}
?>
</nav>

<nav>
<h2>Archive</h2>
<ul>
<? foreach ($archive as $year => $posts) {
	printf("<li><a href=\"?archive=%s\">%s (%s)</a></li>\n", $year, $year, $posts);
} ?>
</ul>
</nav>
<? } ?>
</body>
</html>