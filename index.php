<?php

include __DIR__ . '/func.php';
$route = strtok($_SERVER['REQUEST_URI'], '?');
$re = preg_replace('/^\//s', '', $r);
$router = $r;
if (!$r) {
  $router = 'index';
} else if (preg_match('/\/$/', $r)) {
  $router .= 'index';
}

if (isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == 'application/json') {
} else {
  $jf = __DIR__ . "/views/{$router}.json";
  if (!file_exists($jf)) {
    file_put_contents(
      $jf,
      json_encode([
        'title' => basename($jf, '.json'),
        'desc' =>basename($jf, '.json'),
        'published' => date('m/j/y g:i A'),
        'modified' => date('m/j/y g:i A'),
        'thumbnail' => 'https://1.bp.blogspot.com/-rkXCUBbNXyw/XfY0hwoFu5I/AAAAAAAAAhw/BUyeKW5BtMoIJLlPUcPSdqGZBQRncXjDQCK4BGAYYCw/s600/PicsArt_09-09-12.12.25.jpg'
      ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
  }
  $json = json_decode(file_get_contents($jf));
  $title = $json->title;
  $desc = $json->$desc;
  $published = $json->published;
  $modified = $json->modified;
  theme(__DIR__ . '/content.php', ['title' => 'Telkomsel API', 'desc' => 'Telkomsel API Tools.', 'script' => "views/{$router}.min.js", 'content' => __DIR__ . "/views/{$router}.php"], true);
}
