<?php

function autoload($c) {
  $parts = explode('\\', $c);
  $class = end($parts);
  $vendor = array_shift($parts);
  $path = '';
  if ($vendor == 'aecore') {
    $path .= AE_CORE_DIR;
    switch ($class[0]) {
      case 'A':
        $type .= 'abstracts' . DS;
        break;
      case 'I':
        $type .= 'interfaces' . DS;
        break;
      case 'H':
        $type .= 'helpers' . DS;
        break;
      default:
        $type .= 'classes' . DS;
        break;
    }
    if (file_exists($path . $type . $class . AE_EXT)) {
      require_once $path . $type . $class . AE_EXT;
    } elseif (file_exists($path . 'classes' . $class . AE_EXT)) {
      require_once $path . 'classes' . $class . AE_EXT;
    }
  } else {
    $path .= AE_BASE_DIR;
  }
}

spl_autoload_register('autoload');
