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
             "android" => array("int" =>  "%d", "string" => "%s", "float" => "%.2f"));

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
    }
  }
}

if (file_exists($file)) {
  echo "input file: ".$file."\n";
  $fullfile = file($file);
} else {
  echo "\nERROR: No input file. Use -i <file> option.\n";
  exit;
}
$count = count($fullfile);

//parse first line to get # languages
$firstline = preg_split('/'.$separator.'/', $fullfile[0]);
$languages_count = count($firstline) - constant("kFL");

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


//ios file generation
if ($os == "ios" | $os == "all") {
  $count = count($key);
  foreach ($language as $kk => $vv) {
    for ($j = 1; $j < $count+1; $j++) {
      if (strlen($key[$j]) > 0) {
        if ($type[$j] == "Array") {
          $to_file[$kk][$j] = "/*".$info[$j]."*/\n"."\"".trim($key[$j])."[".$index[$j]."]\""." = "."\"".fix_string($language[$kk][$j],"ios")."\";\n";
        } else {
          $to_file[$kk][$j] = "/*".$info[$j]."*/\n"."\"".trim($key[$j])."\""." = "."\"".fix_string($language[$kk][$j],"ios")."\";\n";
        }
      } else {
        $to_file[$kk][$j] = "\n/* ".$header[$j]." */\n";
      }
    }
  }

  //create files
  $genstr = "/*".$generated."*/\n";
  foreach ($to_file as $keys => $value) {
    file_force_contents($output.$keys.".lproj/Localizable.strings",$genstr.implode($value));
  }
  unset($value);
  unset($keys);
  unset($to_file);
}

//android file generation
if ($os == "android" | $os == "all") {
  $count = count($key);
  foreach ($language as $kk => $vv) {
    for ($j = 1; $j < $count+1; $j++) {
      if (strlen($key[$j]) > 0) {
        if ($type[$j] == "Array") {
          $array_key = addslashes(trim($key[$j]));
          $to_file[$kk][$j] = "  <!-- ".$info[$j]." --> \n  <string-array name=\"".trim($key[$j])."\">\n    <item>".fix_string($language[$kk][$j],"android")."</item>\n";
        $j++;
          while ($type[$j] == "Array" && $array_key == addslashes(trim($key[$j]))) {
            $to_file[$kk][$j] = "    <item>".fix_string($language[$kk][$j],"android")."</item>\n";
            $j++;
          }
          $j--;
          $to_file[$kk][$j] .= "  </string-array>\n";
        } else {
          $to_file[$kk][$j] = "  <!-- ".$info[$j]." --> \n"."  <string name=\"".trim($key[$j])."\">".fix_string($language[$kk][$j],"android")."</string>\n";
        }
      } else {
        $to_file[$kk][$j] = "\n  <!-- ".$header[$j]." --> \n";
      }
    }
  }

  //create files
  $android_file_start = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<resources>\n  <!--".$generated."-->\n";
  $android_file_end = "</resources>\n";
  foreach ($to_file as $keys => $value) {
    file_force_contents($output."values-".$keys."/strings.xml",$android_file_start.implode($value).$android_file_end);
  }
  unset($value);
  unset($keys);
  unset($to_file);
}


?>