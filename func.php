<?php

//require __DIR__ . '/autoload.php';
date_default_timezone_set('Asia/Jakarta');
session_start_timeout(1800, 100, '/');
error_reporting(1);

//use Curl\Curl;

/**
 * JSON formatter.
 *
 * @param array $data
 * @param bool  $header
 * @param bool  $print
 */
function json($data = [], $header = true, $print = true)
{
  if ($header && !headers_sent()) {
    header('Content-Type: application/json');
  }
  $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($print) {
    echo $json;
  } else {
    return $json;
  }
}

/**
 * JSON decode with verification.
 *
 * @param string $str
 */
function jsond($str, $callback = null)
{
  if (isJson($str)) {
    return json_decode($str);
  } elseif (is_callable($callback)) {
    return call_user_func($callback, $str);
  } else {
    throw new Exception('Not valid JSON string', 1);
  }
}

function isJson($string)
{
  json_decode($string);

  return JSON_ERROR_NONE == json_last_error();
}


/**
 * cURL shooter request.
 *
 * @param array $opt
 *
 * @return array
 */
function req($opt)
{
  if (!isset($opt['url'])) {
    throw new Exception('Error Processing Request', 1);
  }
  $msisdn = isset($_SESSION['msisdn']) ? $_SESSION['msisdn'] : 'default';
  //$verbose = __DIR__ . '/otp/http/' . $msisdn . '.' . substr(clean_string(urldecode(urldecode($opt['url']))), 0, 50) . '.txt';
  //file_put_contents($verbose, '');
  //$curl_log = fopen($verbose, 'a');
  $ch = curl_init();
  $result = ['request' => [], 'response' => []];
  if (isset($opt['headers']) && is_array($opt['headers'])) {
    $headers = $opt['headers'];
    if (isset($opt['headers_trim'])) {
      /*$headers = array_map(function ($key) {
      return preg_replace('/\r$/', '', $key);
      }, $headers);*/
      $headers = array_map('trim', $headers);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result['request']['headers'] = $headers;
  }
  curl_setopt($ch, CURLOPT_URL, trim($opt['url']));
  $result['url'] = $opt['url'];
  if (isset($opt['post']) && $opt['post']) {
    curl_setopt($ch, CURLOPT_POST, 1);
    if (isset($opt['postdata'])) {
      if (is_array($opt['postdata'])) {
        $opt['postdata'] = http_build_query($opt['postdata'], '', '&');
      }
      curl_setopt($ch, CURLOPT_POSTFIELDS, $opt['postdata']);
      $result['request']['postdata'] = $opt['postdata'];
    }
  }
  if (isset($opt['cookie']) && true === $opt['cookie'] && isset($_SESSION['cookie'])) {
    $cookie = $_SESSION['cookie'];
    if (!file_exists($cookie)) {
      file_put_contents($cookie, '');
    }
    curl_setopt($ch, CURLOPT_COOKIEJAR, realpath($cookie));
    curl_setopt($ch, CURLOPT_COOKIEFILE, realpath($cookie));
  }
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, true);
  //curl_setopt($ch, CURLOPT_VERBOSE, true);
  //curl_setopt($ch, CURLOPT_STDERR, $curl_log);
  curl_setopt($ch, CURLINFO_HEADER_OUT, true);
  //curl_setopt($ch, CURLOPT_ENCODING, '');
  if (isset($opt['setopt']) && is_array($opt['setopt']) && !empty($opt['setopt'])) {
    foreach ($opt['setopt'] as $key => $value) {
      curl_setopt($ch, $key, $value);
    }
  }

  $data = curl_exec($ch);
  $result['curl_exec'] = $data;

  file_put_contents(__DIR__ . '/otp/http/' . $msisdn, $data);
  //rewind($curl_log);
  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT);
  $headerSent = explode("\n", $headerSent);
  $headerSent = array_map(function ($v) {
    return preg_replace("/\r$/s", '', $v);
  }, $headerSent);
  $result['request']['raw'] = $headerSent;
  $header = substr($data, 0, $header_size);
  $body = substr($data, $header_size);
  if (is_string($body)) {
    $body = json_decode($body, true);
  }

  $header = explode("\n", $header);
  $header = array_map(function ($v) {
    return preg_replace("/\r$/s", '', $v);
  }, $header);

  $result['response']['headers'] = $header;
  $result['response']['body'] = $body;
  $result['options'] = $opt;
  $_SESSION['verbose'][$opt['url']] = $result;
  curl_close($ch);

  return $result;
}

function ev(...$a)
{
  exit(var_dump($a));
}

function clean_string($string)
{
  $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

  return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

/**
 * Parse URL deeper.
 *
 * @param string $url
 * @param bool   $encoded
 *
 * @return array_merge
 */
function parse_url2($url, $encoded = false)
{
  if ($encoded) {
    $url = urldecode($url);
  }
  $url = html_entity_decode($url);
  $parts = parse_url($url);
  if (isset($parts['query'])) {
    parse_str($parts['query'], $query);
    $parts['original_query'] = $parts['query'];
    $parts['query'] = $query;
  }

  return array_merge($parts);
}

function verifyCaptcha($callback = null)
{
  $opt['url'] = 'https://www.google.com/recaptcha/api/siteverify?secret=' . DE('elNhc3Bg9HMH32ze37zweRIad7jOcoOuiGvEq3+WvtMwccym9cUKKT4jW3BYq4Pw') . '&response=' . $_POST['g-recaptcha-response'];
  $req = req($opt);
  if (isset($req['response']['body']['success'])) {
    if ($req['response']['body']['success']) {
      if (is_callable($callback)) {
        return call_user_func($callback);
      }
    }
  } else {
    json($req);
  }
}

function isLocal()
{
  return preg_match('/agc\.io|127\.0\.0\.0\.1|localhost/s', $_SERVER['HTTP_HOST']);
}

/***
 * Starts a session with a specific timeout and a specific GC probability.
 * @param int $timeout The number of seconds until it should time out.
 * @param int $probability The probablity, in int percentage, that the garbage
 *        collection routine will be triggered right now.
 * @param strint $cookie_domain The domain path for the cookie.
 */
function session_start_timeout($timeout = 5, $probability = 100, $cookie_domain = '/')
{
  // Set the max lifetime
  ini_set('session.gc_maxlifetime', $timeout);

  // Set the session cookie to timout
  ini_set('session.cookie_lifetime', $timeout);

  // Set session directory save path
  ini_set('session.save_path', __DIR__ . '/sessions');

  // Change the save path. Sessions stored in teh same path
  // all share the same lifetime; the lowest lifetime will be
  // used for all. Therefore, for this to work, the session
  // must be stored in a directory where only sessions sharing
  // it's lifetime are. Best to just dynamically create on.
  $seperator = strstr(strtoupper(substr(PHP_OS, 0, 3)), 'WIN') ? '\\' : '/';
  $path = ini_get('session.save_path') . $seperator . 'session_' . $timeout . 'sec';
  if (!is_dir(dirname($path))) {
    if (!mkdir(dirname($path), 600)) {
      trigger_error("Failed to create session save path directory '$path'. Check permissions.", E_USER_ERROR);
    }
  }
  if (!file_exists($path)) {
    if (!mkdir($path, 600)) {
      trigger_error("Failed to create session save path directory '$path'. Check permissions.", E_USER_ERROR);
    }
  }
  ini_set('session.save_path', $path);

  // Set the chance to trigger the garbage collection.
  ini_set('session.gc_probability', $probability);
  ini_set('session.gc_divisor', 100); // Should always be 100

  // Start the session!
  session_start();

  // Renew the time left until this session times out.
  // If you skip this, the session will time out based
  // on the time when it was created, rather than when
  // it was last used.
  if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), $_COOKIE[session_name()], time() + $timeout, $cookie_domain);
  }
}

/**
 * Include passed variable.
 *
 * @param string $filePath
 * @param array  $variables
 * @param bool   $print
 */
function theme($filePath, $variables = [], $print = true)
{
  $output = null;
  if (file_exists($filePath)) {
    // Extract the variables to a local namespace
    extract($variables);
    $_SESSION['var'] = get_defined_vars();
    // Start output buffering
    ob_start();

    // Include the template file
    include $filePath;

    // End buffering and return its contents
    $output = ob_get_clean();
  }
  if ($print) {
    echo $output;
  }

  return $output;
}

/**
 * echo print_r in pretext.
 *
 * @param mixed $str
 */
function printr($str, $str1 = 0, $str2 = 0)
{
  echo '<pre>';
  print_r($str);
  if ($str1) {
    print_r($str1);
  }
  if ($str2) {
    print_r($str2);
  }
  echo '</pre>';
}

/**
 * echo json_encode in pretext.
 */
function precom(...$str)
{
  $D = json_encode($str, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  if (headers_sent()) {
    echo '<pre class="notranslate">';
    echo $D;
    echo '</pre>';
  } else {
    return $D;
  }
}

// ======= Encryption
const SALT = 'salt'; //salt
const IV = '1111111111111111'; //pass salt minimum length 12 chars or it'll be show warning messages
const ITERATIONS = 999; //iterations
/**
 * Encrypt.
 *
 * @see https://web-manajemen.blogspot.com/2019/07/phpjs-cryptojs-encrypt-decrypt.html
 *
 * @param string $passphrase
 * @param string $plainText
 *
 * @return string
 */
function EN($plainText, $passphrase = 'dimaslanjaka')
{
  $key = \hash_pbkdf2('sha256', $passphrase, SALT, ITERATIONS, 64);
  $encryptedData = \openssl_encrypt($plainText, 'AES-256-CBC', \hex2bin($key), OPENSSL_RAW_DATA, IV);

  return \base64_encode($encryptedData);
}
/**
 * Decrypt.
 *
 * @see https://web-manajemen.blogspot.com/2019/07/phpjs-cryptojs-encrypt-decrypt.html
 *
 * @param string $passphrase
 * @param string $encryptedTextBase64
 *
 * @return string
 */
function DE($encryptedTextBase64, $passphrase = 'dimaslanjaka')
{
  $encryptedText = \base64_decode($encryptedTextBase64);
  $key = \hash_pbkdf2('sha256', $passphrase, SALT, ITERATIONS, 64);
  $decryptedText = \openssl_decrypt($encryptedText, 'AES-256-CBC', \hex2bin($key), OPENSSL_RAW_DATA, IV);

  return $decryptedText;
}
