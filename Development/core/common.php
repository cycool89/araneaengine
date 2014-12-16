<?php

/**
 * Megadott <var>$pathname</var>-n belül be require_once-olja az összes
 * <var>$file_ext</var> kiterjesztésű fájlt.
 * 
 * Ha nincs megadva <var>$file_ext</var>, akkor az összes tartalmazott fájlt.
 * 
 * <var>$one</var> true esetén pontos fájl require-olás.
 * 
 * Visszatérési érték a talált fájlok.
 * 
 * @param string $pathname
 * @param string $file_ext
 * @param string $one
 * @return array
 */
function req_dir($pathname, $file_ext = 'all', $one = false) {
  if (!$one) {
    ($file_ext == 'all') ? $file_ext = '*' : $file_ext = '*.' . $file_ext;
    $file_ext = str_replace('..', '.', $file_ext);
  }
  $files = glob($pathname . $file_ext);
  foreach ($files as $filename) {
    require_once $filename;
  }
  return $files;
}

/**
 * mkdir rekurzívan, jogosultságokkal
 * 
 * @param string dir
 */
function mkdir_r($dir, $mode = 0775) {
  $parts = explode(DS, $dir);
  $dir = array_shift($parts);
  foreach ($parts as $part) {
    if (!is_dir($dir .= DS . $part)) {
      $old = umask(0);
      mkdir($dir, $mode);
      umask($old);
      chmod($dir, $mode);
    }
  }
}

/**
 * PHP file_put_contents csak ez létrehozza a mappákat is.
 * 
 * @param string $dir
 * @param string $contents
 */
function file_force_contents($dir, $contents, $mode = 0775) {
  $parts = explode(DS, $dir);
  $file = array_pop($parts);
  $dir = array_shift($parts);
  foreach ($parts as $part) {
    if (!is_dir($dir .= DS . $part)) {
      $old = umask(0);
      mkdir($dir, $mode);
      umask($old);
      chmod($dir, $mode);
    }
  }
  file_put_contents($dir . DS . $file, $contents);
  chmod($dir . DS . $file, $mode);
}

function tobbesszam($string) {
  $string = strtolower($string);
  $maganhangzok = array("a", "e", "i", "o", "u");
  $kivetelek = array(
      "man" => "men",
      "woman" => "women",
      "child" => "children",
      "mouse" => "mice",
  );

  if (in_array($string, array_keys($kivetelek))) {
    //Ha rendhagyó
    $string = $kivetelek[$string];
  } else {
    //Ha nem rendhagyó
    $utso_betu = array("s", "x", "z");
    if (in_array($string[strlen($string) - 1], $utso_betu)) {
      //Ha utsó betűje S,X vagy Z
      $string .= "es";
    } elseif (($string[strlen($string) - 2] == "c" || $string[strlen($string) - 2] == "s") && $string[strlen($string) - 1] == "h") {
      //Ha utsó előtti (c vagy s) és utsó = h
      $string .= "es";
    } elseif (!in_array($string[strlen($string) - 2], $maganhangzok) && $string[strlen($string) - 1] == "y") {
      //Ha utsóelőtti nem magámhangzó és utsó = y
      $string[strlen($string) - 1] = "i";
      $string .= "es";
    } else {
      //Normál szó
      $string .= "s";
    }
  }

  return $string;
}

function autoload($c) {
  $parts = explode('\\', $c);
  $class = end($parts);
  $path = '';
  switch ($class[0]) {
    case 'i':
      $path = 'Interfaces';
      break;
    case 'a':
      $path = 'Abstracts';
      break;
    case 's':
      $path = 'Singletons';
      break;
    default:
      $path = 'Classes';
      break;
  }
  if (file_exists(AE_CORE_DIR . $path . DS . $class . AE_EXT)) {
    require_once AE_CORE_DIR . $path . DS . $class . AE_EXT;
  }
}

spl_autoload_register('autoload');

function getClassName($c) {
  if (is_object($c))
    $c = get_class($c);
  $parts = explode('\\', $c);
  $class = end($parts);
  return $class;
}

function get_memory_usage() {
  $mem_usage = memory_get_usage(true);

  $ret = '';
  if ($mem_usage < 1024) {
    $ret .= $mem_usage . " bytes";
  } elseif ($mem_usage < 1048576) {
    $ret .= round($mem_usage / 1024, 2) . " kilobytes";
  } else {
    $ret .= round($mem_usage / 1048576, 2) . " megabytes";
  }
  return $ret;
}
