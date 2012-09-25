<?php

$url = "http://datastory.de/?post_type=datawrapper&custom_fields=dw_url,dw_lang,dw_show_page&json=1";

$userpwd = getenv('DATASTORY_AUTH');

$p = curl_init();
curl_setopt($p, CURLOPT_URL, $url);
curl_setopt($p, CURLOPT_HEADER, 0);
curl_setopt($p, CURLOPT_USERPWD, $userpwd);
curl_setopt($p, CURLOPT_RETURNTRANSFER, 1);
$ret = curl_exec($p);

//$data = json_decode(file_get_contents($url));

$autogen_note = "\tThis file was automatically generated by update_content_from_datastory.php.\n\tAny changes that you make will be overwritten on next run.";
$autogen_php = "\n/*\n" . $autogen_note . "\n*/\n";
$autogen_twig = "\n{#\n" . $autogen_note . "\n#}\n";

$data = json_decode($ret);

$header = "{% extends \"docs.twig\" %}\n{% block docscontent %}\n".$autogen_twig."\n";
$footer = "\n{% endblock %}";

$pages = array();

foreach ($data->posts as $post) {
    if ($post->status == "publish") {
        $lang = $post->custom_fields->dw_lang[0];
        $url = $post->custom_fields->dw_url[0];
        $tpl = $header . "\n<article> <!-- begin wordpress content -->\n\n" . $post->content . "\n\n</article> <!-- end wordpress content -->\n" . $footer;
        $tpl_file = "../templates/imported/" . $lang
                  . "/" . str_replace('/', '-', $url) . ".twig";
        file_put_contents($tpl_file, $tpl);
        $show_page = isset($post->custom_fields->dw_show_page) && $post->custom_fields->dw_show_page[0] == "1";
        
        if (!isset($pages[$lang])) $pages[$lang] = array();
        $pages[$lang][$url] = array(
            'title' => $post->title,
            'show' => $show_page
        );
    }
}

file_put_contents("../templates/imported/pages.inc.php", "<?php\n$autogen_php\n\$docs_pages = " . var_export($pages, true) . ";\n");
