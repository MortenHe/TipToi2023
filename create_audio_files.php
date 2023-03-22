<?php

//TODO: config
$audioDir = "C:/Users/Martin/Desktop/Nextcloud/TipToi2023/Musescore-audio";

//Files fuer die Audio erstellt wird
$names = [
  "noten_lesen_01_1",
  //  "noten_lesen_01_2",
  //  "noten_lesen_01_3",
  //  "noten_lesen_02_1",
  //  "noten_lesen_02_2",
  //  "noten_lesen_02_3",
  //"rhythmus_01_1",
  //"rhythmus_01_2",
  //"rhythmus_01_3",
  //"song_01_wo_bist_du",
];

//Liste der Tempos
$tempos = [
  "snail" => 60,
  "horse" => 70,
  "cheetah" => 80
];

//Taktart
$timeSignature = "4_4";

//Audio erstellen
foreach ($names as $name) {

  //Mscz -> Musicxml
  $musicxmlPath = "{$audioDir}/Temp/" . $name . ".musicxml";
  shell_exec("MuseScore3.exe {$audioDir}/" . $name . ".mscz -o " . $musicxmlPath);

  //Musicxml laden, hier kann man das Tempo aendern
  $domdoc = new DOMDocument();
  $domdoc->loadXML(file_get_contents($musicxmlPath));
  $xpath = new DOMXPath($domdoc);

  //Tempo-Tag auslesen
  $tempoTag = $xpath->query("//sound[@tempo]")->item(0);

  //Lautstaerke-Tags holen (Klavier 1, Klavier 2, Gitarre, div. Percussions)
  //$score_parts = $xpath->query("//score-part/*/volume");

  /*
    //Ueber Uebungen (z.B. rechte Hand, linke Hand) gehen
    foreach ($project_config["rows"] as $row) {

        //ID fuer Benennung der files
        $row_id = $row["id"];

        //Zunaechst allen Instrumenten die Lautstaerke 78 geben
        foreach ($score_parts as $score_part) {
            $score_part->nodeValue = 78;
        }

        //Wenn in dieser Uebung ein Instrument gemutet werden soll (z.B. linke Hand gemutet), ueber die Indexe der gemuteten Instrumente gehen
        //und Lautstaerke auf 0 setzen
        if (isset($row["mute"])) {
            foreach ($row["mute"] as $mute_idx) {
                $score_parts->item($mute_idx)->nodeValue = 0;
            }
        }
        */

  //Ueber Tempos einer Uebung gehen
  foreach ($tempos as $tempoName => $tempo) {

    //PHP 8 $tempoTag = (DOMElement) $tempoTag;
    //Tempo-Tag auf passenden Wert setzen (z.B. 60)
    if ($tempoTag instanceof DOMElement) {
      $tempoTag->setAttribute("tempo", $tempo);
    }

    //musicxml-Datei mit angepasstem XML (Tempo, ggf. gemutete Instrumente) speichern
    $tempoMusicxmlPath = "{$audioDir}/Temp/" . $name . "_" . $tempoName . ".musicxml";
    $fh = fopen($tempoMusicxmlPath, "w");
    fwrite($fh, $domdoc->saveXML());
    fclose($fh);

    //Tempo-musicxml -> Tempo-mscz fuer mp3 Erzeugung
    $tempoMsczlPath = "{$audioDir}/Temp/" . $name . "_" . $tempoName . ".mscz";
    shell_exec("MuseScore3.exe " . $tempoMusicxmlPath . " -o " . $tempoMsczlPath);

    //mp3-Erzeugung
    $mp3Path = "{$audioDir}/Temp/" . $name . "_" . $tempoName . ".mp3";
    shell_exec("MuseScore3.exe " . $tempoMsczlPath . " -o " . $mp3Path);

    //countInFile + mp3-File mergen
    $countInFile = "{$audioDir}/Count-in/" . $tempo . "_" . $timeSignature . ".mp3";
    $finalFile = "{$audioDir}/" . $name . "_" . $tempoName . "_01.mp3";
    $mergeCommand = 'ffmpeg -y -hide_banner -loglevel panic -i "concat:' . $countInFile . '|' . $mp3Path . '" -acodec copy ' . $finalFile;
    shell_exec($mergeCommand);
  }
}

//Temp-Ordner leeren
foreach (glob("{$audioDir}/Temp/*") as $tempFile) {
  unlink($tempFile);
}
