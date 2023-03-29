<?php

require_once('config.php');

//Codes, YAML und GME Datei erstellen fuer Notenbuch-Prototyp

//TODO: find unneeded mp3

//In Verzeichnis wechseln, damit dort die YAML, GME und Codes-Dateien erstellt werden
chdir($yamlDir);
$outputName = "notenbuch.yaml";

//Inits fuer Scripte mit mehreren Soundfiles pro Code
$inits = [];

//Scripts erstellen
$scripts = [];
foreach ($data as $block => $blockData) {

  //Kommentar in welchem Block wir sind: #01-das-klavier
  $scripts[] = "\n  #" . $block;

  //Codes pro Block erzeugen
  foreach ($blockData as $code) {
    $name = $code[0];
    $count = isset($code[1]) ? $code[1] : null;

    //Einzelner Sound: code: P(code_01)
    if (!$count) {
      $single_code = "  " . $name . ": P(" . $name . "_01)";
      $scripts[] = $single_code;
    } else {
      switch ($code[2]) {

          //Mehrere Soundfiles nacheinander: code: P(code_01) P(code_02) P(code_03)
        case "single":
          $single_line = "  " . $name . ":";
          for ($i = 1; $i <= $count; $i++) {
            $single_line .= " P(" . $name . "_0" . $i . ")";
          }
          $scripts[] = $single_line;
          break;

          //Durch Soundfiles gehen bei mehrmaligem Tippen:  keys: \n- $keys==0? P(keys_01) $keys+=1 $keys%=3
        case "multi":
          $inits[] = "$" . $name . ":=0";
          $multiline = "  " . $name . ":\n";
          for ($i = 1; $i <= $count; $i++) {
            $multiline .= "   - $" . $name . "==" . ($i - 1) . "? P(" . $name . "_0" . $i . ") $" . $name . "+=1 $" . $name . "%=" . $count;
            if ($i < $count) {
              $multiline .= "\n";
            }
          }
          $scripts[] = $multiline;
          break;
      }
    }
  }
}

//YAML-Datei schreiben
$output .= "\ninits: " .  join(" ", $inits);
$output .= "\nscripts:\n";
$output .= join("\n", $scripts);
$fh = fopen($outputName, "w");
fwrite($fh, $output);
fclose($fh);

//Bilder in YAML-Ordner loeschen
foreach (glob("*.png") as $oldImg) {
  unlink($oldImg);
}

//Codes erstellen
$out = shell_exec("tttool.exe oid-codes " . $outputName . " --pixel-size 4 --code-dim 15");
echo $out;

//Codes ohne oid-Praefix in Codes-Ordner verschieben
foreach (glob("*.png") as $img) {
  $newName = str_replace("oid-925-", "", $img);
  rename($img, "Codes/" . $newName);
}

//GME-Datei erstellen
$outAssemble = shell_exec("tttool.exe assemble " . $outputName);
