<?php

//Codes, YAML und GME Datei erstellen fuer Notenbuch-Prototyp

//In Verzeichnis wechseln, damit dort die YAML, GME und Codes-Dateien erstellt werden
//TODO: config
$yamlDir = "C:/Users/Martin/Desktop/Nextcloud/TipToi2023/YAML";

chdir($yamlDir);
$outputName = "notenbuch.yaml";

//Headerbereich der YAML-Datei
$output = "product-id: 925
comment: \"Notenbuch von Martin Helfer\"
welcome: start, welcome
gme-lang: GERMAN
media-path: Audio/%s";

//TODO: config
//Scripts der YAML-Datei
$data = [
  "01-das-klavier" => [
    ["klavier", 5, "single"],
    ["glissando"],
    ["klavier_tief", 2, "multi"],
    ["klavier_mittel", 2, "multi"],
    ["klavier_hoch", 2, "multi"],
    ["keys", 3, "multi"],
    ["der_ton_c"],
    ["draw_c_und_d", 2, "multi"],
    ["words_with_c"],
    ["der_ton_d"],
    ["words_with_d"],
  ],
  "02-die-notenschrift" => [
    ["notenschrift", 4, "single"],
    ["viertelnote", 2, "multi"],
    ["notenlinien", 3, "multi"],
    ["notenlinien_mit_noten", 2, "multi"],
    ["notenlinien_mit_allem", 2, "multi"],
    ["noten_c_und_d"],
    ["draw_noten_c_und_d", 2, "multi"],
  ],
  "03-noten-lesen-01" => [
    ["uebung_01_explain", 2, "multi"],
    ["faster_than_cheetah", 2, "multi"],
    ["fingers_01_explain"],
    ["fingers_01_repeat"],
    ["stop_explain"],

    ["noten_lesen_01_1_snail"],
    ["noten_lesen_01_1_horse"],
    ["noten_lesen_01_1_cheetah"],
    ["noten_lesen_01_2_snail"],
    ["noten_lesen_01_2_horse"],
    ["noten_lesen_01_2_cheetah"],
    ["noten_lesen_01_3_snail"],
    ["noten_lesen_01_3_horse"],
    ["noten_lesen_01_3_cheetah"],
  ],
  "04-der-ton-e" => [
    ["der_ton_e"],
    ["draw_e", 2, "multi"],
    ["words_with_e"],
    ["recognize_notes_e", 2, "multi"],
    ["write_notes_e"],
  ],
  "05-noten-lesen-02" => [
    ["uebung_02_explain"],
    ["fingers_02_explain", 2, "multi"],
    ["fingers_02_repeat"],
    ["notes_and_fingers_short"],

    ["noten_lesen_02_1_snail"],
    ["noten_lesen_02_1_horse"],
    ["noten_lesen_02_1_cheetah"],
    ["noten_lesen_02_2_snail"],
    ["noten_lesen_02_2_horse"],
    ["noten_lesen_02_2_cheetah"],
    ["noten_lesen_02_3_snail"],
    ["noten_lesen_02_3_horse"],
    ["noten_lesen_02_3_cheetah"],
  ],
  "06-viertelpause" => [
    ["viertelpause"],
    ["make_a_break", 2, "multi"],
    ["rest_01_explain", 2, "multi"],

    ["rhythmus_01_1_snail"],
    ["rhythmus_01_1_horse"],
    ["rhythmus_01_1_cheetah"],
    ["rhythmus_01_2_snail"],
    ["rhythmus_01_2_horse"],
    ["rhythmus_01_2_cheetah"],
    ["rhythmus_01_3_snail"],
    ["rhythmus_01_3_horse"],
    ["rhythmus_01_3_cheetah"],
  ],

  "07-wo-bist-du" => [
    ["song_01_explain"],
    ["wo_bist_du", 2, "multi"],
    ["summary_01_explain"],
    ["summary_piano"],
    ["summary_c"],
    ["summary_d"],
    ["summary_e"],
    ["summary_viertelnote"],
    ["summary_viertelpause"],
    ["summary_notenlinien"],
    ["summary_violinschluessel"],
    ["summary_vier_viertel_takt"],
    ["summary_01_fingersatz"],
    ["summary_notenlinie_komplett", 2, "multi"],

    ["song_01_wo_bist_du_snail"],
    ["song_01_wo_bist_du_horse"],
    ["song_01_wo_bist_du_cheetah"],
  ],
];

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
