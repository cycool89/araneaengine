<?php

namespace core;

/**
 * Description of Log
 *
 * @author cycool89
 */
class Log {

  static function write($msg, $show = false, $exit = false, $level = 0) {
    $debug = self::debugPrintCallingFunction($level);
    $time = date("Y. m. d. H:i:s");
    $file = AE_BASE_DIR . 'ae_error.log';
    $content = '';
    if (file_exists($file)) {
      $content = file_get_contents($file);
    } else {
      $content = "\nLog file:\n";
      $content .= "AraneaEngine version: " . AraneaEngine::VERSION . "\n";
      $content .= "Created: $time";
    }
    $new = "\n$time : $msg\n";
    $new .= "\t{$debug['file']}\n\t\${$debug['class']}->{$debug['func']}() : {$debug['line']}\n";
    $content = $new . $content;
    file_force_contents($file, $content);

    if ($show && AE_DEBUG_MODE) {
      echo "<pre>$time : <b>$msg</b>\n";
      echo "\t{$debug['file']}\n\t\${$debug['class']}->{$debug['func']} : {$debug['line']}</pre>";
    }
    if ($exit) {
      exit();
    }
  }
  
  static function debugPrintCallingFunction($level = 0) {
    $file = 'n/a';
    $func = 'n/a';
    $line = 'n/a';
    $debugTrace = debug_backtrace();
    //echo d($debugTrace);
    if (isset($debugTrace[$level + 1])) {
      $file = $debugTrace[$level + 1]['file'] ? $debugTrace[$level + 1]['file'] : 'n/a';
      $line = $debugTrace[$level + 1]['line'] ? $debugTrace[$level + 1]['line'] : 'n/a';
    }
    if (isset($debugTrace[$level + 2])) {
      $func = $debugTrace[$level + 2]['function'] ? $debugTrace[$level + 2]['function'] : 'n/a';
      $class = $debugTrace[$level + 2]['class'] ? $debugTrace[$level + 2]['class'] : 'n/a';
    }
    return array(
      'file' => AE_BASE_PATH . str_replace(AE_BASE_DIR, '', $file),
      'class' => getClassName($class),
      'func' => $func,
      'line' => $line
    );
  }

}
