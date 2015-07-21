<?php
//column indexes
// 0 column adds headings
define("kDESC", 1);  // description
define("kTYPE", 2);  // type
define("kINDEX", 3); // index
define("kKEY", 4);   // key
define("kFL", 5);    // first language column

//replace array
$replace = array("ios" => array("int" => "%ld", "string" => "%@", "float" => "%.2f"),
             "android" => array("int" =>  "%d", "string" => "%s", "float" => "%.2f"),
             "laravel" => array("int" =>  "%d", "string" => "%s", "float" => "%.2f"),
             );

// helper functions
function fix_string($string,$os)
{
  global $replace;
  if (preg_match_all("/{(.+?)}/",$string,$match))
  {
    for ($t = 0; $t < count($match[0]);$t++) {
      $string = str_replace($match[0][$t], $replace[$os][$match[1][$t]], $string);
    }
  }
  if ($os == "android") {
    $string = htmlspecialchars($string,ENT_NOQUOTES);
  }
  if ($os == "laravel") {
    $string = htmlspecialchars($string,ENT_QUOTES);
  }

  $fix_new_line = str_replace("\\\\n", "\\n", addslashes($string));
  return $fix_new_line;
}


function file_force_contents($dir, $contents)
{
  $parts = explode('/', $dir);
  $file = array_pop($parts);
  $dir = '';
  foreach($parts as $part) {
    if(!is_dir($dir .= "$part/")) {
      mkdir($dir);
    }
  }
  file_put_contents("$dir/$file", $contents);
}

//get arguments
$os = "all";
$file = "";
$output = "./";
$separator = "\t";
$mf = FALSE;
$force = FALSE;
for ($i = 1; $i < count($argv);$i++) {
  $option = strstr($argv[$i],"-");
  if (count($option) > 0){
    switch ($option) {
      case "-ios" : {
        $os = "ios";
        break;
      }
      case "-android" : {
        $os = "android";
        break;
      }
      case "-laravel" : {
        $os = "laravel";
        break;
      }
      case "-all" : {
        $os = "all";
        break;
      }
      case "-i" : {
        $file = $argv[$i+1];
        break;
      }
      case "-o" : {
        if  (strrpos($argv[$i+1],"/") != strlen($argv[$i+1])-1) {
          $output  = $argv[$i+1]."/";
        } else {
          $output = $argv[$i+1];
        }
        break;
      }
      case "-separator" : {
        $separator = $argv[$i+1];
        break;
      }
      case "-multifile" : {
        $mf = TRUE;
        break;
      }
      case "-force" : {
        $force = TRUE;
        break;
      }
      case "-of" : {
        $output_file_name = $argv[$i+1];
        break;
      }
      case "-link" : {
        $link = $argv[$i+1];
        break;
      }
      case "-linkfile" : {
        $linkfile = $argv[$i+1];
        break;
      }
    }
  }
}

if (file_exists($file) || isset($link) || isset($linkfile)) {
  echo "input file/link/linkFile: ".$file.$link.$linkfile."\n";
  if (isset($linkfile)) {
    if ($os != "laravel") {
      echo "ERROR: linkfile only supported for Laravel (php arrays)".PHP_EOL;
      exit;
    }
    require($linkfile);
  } else if (isset($link)) {
    $fullfile = file($link);
  } else {
    $fullfile = file($file);
  }
} else {
  echo "\nERROR: No input file. Use -i <file>, -link <link> or -linkfile <file> option.\n";
  exit;
}

if (isset($fileArray)) {
  foreach ($fileArray as $key => $value) {
    unset($output_file_name);
    if (strlen($value) >= 100+3) {
      echo "-> key: ".$key." value: ".substr($value, 0, 50). "..." . substr($value, -50).PHP_EOL;
    }
    else {
      echo "-> key: ".$key." value: ".$value.PHP_EOL;
    }
    parse(file($fileArray[$key]));
  }
} else {
  parse($fullfile);
}


function parse($fullfile) {
  
  global $separator;
  global $os;
  global $file;
  global $output;
  global $separator;
  global $mf;
  global $force;
  global $output_file_name;

  $count = count($fullfile);

  //parse first line to get # languages
  $firstline = preg_split('/'.$separator.'/', $fullfile[0]);
  $languages_count = count($firstline) - constant("kFL");

  //get output filename for Laravel
  if (!isset($output_file_name) && ($os == "laravel" || $os == "all")) {
    $output_file_name = strtolower($firstline[0]);
    if (strlen($output_file_name) < 1) {
      echo "ERROR: No output file name! Use '-of <name>'' option or add file name to first cell of the input file".PHP_EOL;
      exit;
    }
  } else {
    if (strlen($output_file_name) < 1 && ($os == "laravel" || $os == "all")) {
      echo "ERROR: No output file name! Use '-of <name>'' option or add file name to first cell of the input file".PHP_EOL;
      exit;
    }
  }

  //create language array
  for ($k = 1; $k < $count;$k++) {
    $linearray = preg_split('/'.$separator.'/', $fullfile[$k]);
    $header[$k] = $linearray[0];
    $info[$k] = $linearray[kDESC];
    $type[$k] = $linearray[kTYPE];
    $key[$k] = $linearray[kKEY];
    $index[$k] = $linearray[kINDEX];
    for ($h = 0; $h < $languages_count; $h++){
      $language[trim(strtolower($firstline[$h+kFL]))][$k] = trim($linearray[$h+kFL]);
    }
  }

  //generation date
  date_default_timezone_set("UTC");
  $generated = "File generated: ".date("j.n.Y - H:i:s e",time());

  $ignoreLanguage = array();

  //ios file generation
  if ($os == "ios" | $os == "all") {
    $count = count($key);
    foreach ($language as $kk => $vv) {
      for ($j = 1; $j < $count+1; $j++) {
        if (strlen($key[$j]) > 0) {
          if (!strlen($language[$kk][$j]) > 0 && !$force) {
            $ignoreLanguage[$kk] = 1;
          }
          if (strlen($language[$kk][$j]) > 0) {
            if ($type[$j] == "Array") {
              $to_file[$kk][$j] = "/*".$info[$j]."*/\n"."\"".trim($key[$j])."[".$index[$j]."]\""." = "."\"".fix_string($language[$kk][$j],"ios")."\";\n";
            } else {
              $to_file[$kk][$j] = "/*".$info[$j]."*/\n"."\"".trim($key[$j])."\""." = "."\"".fix_string($language[$kk][$j],"ios")."\";\n";
            }
          }
        } else {
          $to_file[$kk][$j] = "\n/* ".$header[$j]." */\n";
        }
      }
    }

    //create files
    $genstr = "/*".$generated."*/\n";
    foreach ($to_file as $keys => $value) {
      if  (!isset($ignoreLanguage[$keys])) {
        file_force_contents($output.$keys.".lproj/Localizable.strings",$genstr.implode($value));
      }
    }
    unset($value);
    unset($keys);
    unset($to_file);
    unset($ignoreLanguage);
  }

  //android file generation
  if ($os == "android" | $os == "all") {
    $count = count($key);
    foreach ($language as $kk => $vv) {
      for ($j = 1; $j < $count+1; $j++) {
        if (strlen($key[$j]) > 0) {
          if (!strlen($language[$kk][$j]) > 0 && !$force) {
            $ignoreLanguage[$kk] = 1;
          }
          if (strlen($language[$kk][$j]) > 0) {
            if ($type[$j] == "Array") {
              $array_key = addslashes(trim($key[$j]));
              $to_file[$kk][$headers][$j] = "  <!-- ".$info[$j]." --> \n  <string-array name=\"".trim($key[$j])."\">\n    <item>".fix_string($language[$kk][$j],"android")."</item>\n";
            $j++;
              while ($type[$j] == "Array" && $array_key == addslashes(trim($key[$j]))) {
                $to_file[$kk][$headers][$j] = "    <item>".fix_string($language[$kk][$j],"android")."</item>\n";
                $j++;
              }
              $j--;
              $to_file[$kk][$headers][$j] .= "  </string-array>\n";
            } else {
              $to_file[$kk][$headers][$j] = "  <!-- ".$info[$j]." --> \n"."  <string name=\"".trim($key[$j])."\">".fix_string($language[$kk][$j],"android")."</string>\n";
            }
          }
        } else {
          $headers = $header[$j];
          $to_file[$kk][$headers][$j] = "\n  <!-- ".$header[$j]." --> \n";
        }
      }
    }

    //create files
    $android_file_start = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<resources>\n  <!--".$generated."-->\n";
    $android_file_end = "</resources>\n";

    if ($mf) {
      foreach ($to_file as $keys => $values) {
        if  (!isset($ignoreLanguage[$keys])) {
          foreach ($values as $key => $value) {
            file_force_contents($output."values-".$keys."/".strtolower($key)."-strings.xml",$android_file_start.implode($value).$android_file_end);
          }
        }
      }
    } else {
      foreach ($to_file as $keys => $values) {
        if  (!isset($ignoreLanguage[$keys])) {
          $gen_string = $android_file_start;
          foreach ($values as $key => $value) {
            $gen_string .=implode($value);
          }
          $gen_string .= $android_file_end;
          file_force_contents($output."values-".$keys."/strings.xml",$gen_string);
        }
      }
    }
    unset($value);
    unset($keys);
    unset($to_file);
    unset($ignoreLanguage);
  }


  //laravel file generation
  if ($os == "laravel" | $os == "all") {
    $count = count($key);
    foreach ($language as $kk => $vv) {
      for ($j = 1; $j < $count+1; $j++) {
        if (strlen($key[$j]) > 0) {
          if (!strlen($language[$kk][$j]) > 0 && !$force) {
            $ignoreLanguage[$kk] = 1;
          }
          if (strlen($language[$kk][$j]) > 0) {
            if ($type[$j] == "Array") {
              $to_file[$kk][$j] = "    /*".$info[$j]."*/".PHP_EOL."    \"".trim($key[$j])."_".$index[$j]."\""." => "."\"".fix_string($language[$kk][$j],"laravel")."\",".PHP_EOL;
            } else {
              $to_file[$kk][$j] = "    /*".$info[$j]."*/".PHP_EOL."    \"".trim($key[$j])."\""." => "."\"".fix_string($language[$kk][$j],"laravel")."\",".PHP_EOL;
            }
          }
        } else {
          $to_file[$kk][$j] = PHP_EOL."    /* ".$header[$j]." */".PHP_EOL;
        }
      }
    }

    //create files
    $laravel_file_start = "<?php".PHP_EOL."  /*".$generated."*/".PHP_EOL."  return [";
    $laravel_file_end = "  ];".PHP_EOL."?>".PHP_EOL;

    foreach ($to_file as $keys => $value) {
      if  (!isset($ignoreLanguage[$keys])) {
        file_force_contents($output.$keys."/".$output_file_name.".php",$laravel_file_start.implode($value).$laravel_file_end);
      }
    }
    unset($value);
    unset($keys);
    unset($to_file);
    unset($ignoreLanguage);
  }

}









?>