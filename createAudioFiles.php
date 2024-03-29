<?php
require_once('config.php');

emptyDirectory("{$audioDir}/Temp");

//Signalton vor Einzaehler
$preCountInFile = "{$audioDir}/Count-in/pre_count_in.mp3";

//Audio Dateien in verschiedenen Tempi erstellen aus Musescore Dateien
foreach ($names as $name) {

  //mscz zu xml extrahieren
  $zip = new ZipArchive;
  $zip->open("{$audioDir}/{$name}.mscz");
  $zip->extractTo("{$audioDir}/Temp");
  $zip->close();

  //XML laden, hier kann man das Tempo aendern
  $mscxPath = "{$audioDir}/Temp/" . $name . ".mscx";
  $xml = simplexml_load_file($mscxPath);
  $first_tempo = $xml->xpath('//tempo')[0];

  //Ueber Tempos einer Uebung gehen
  foreach ($tempos as $tempoName => $tempo) {
    echo "create {$name} - {$tempo['value']}\n";
    $first_tempo[0] = $tempo["mult"];
    $xml->asXML($mscxPath);

    //mp3-Erzeugung
    $mp3Path = "{$audioDir}/Temp/" . $name . "_" . $tempoName . ".mp3";
    shell_exec("MuseScore4.exe " . $mscxPath . " -o " . $mp3Path);

    //mp3 normalisieren mit ffmpeg (bis 06.23)
    //$mp3NormPath = "{$audioDir}/Temp/" . $name . "_" . $tempoName . "_norm.mp3";
    //shell_exec("ffmpeg -y -hide_banner -loglevel panic -i {$mp3Path} -af loudnorm -ar 44100 {$mp3NormPath}");

    //mp3 normalisieren mit mp3gain (ab 06.23)
    shell_exec("mp3gain -r {$mp3Path}");

    //countInFile + mp3-File mergen
    $countInFile = "{$audioDir}/Count-in/" . $tempo["value"] . "_{$timeSignature}.mp3";
    $finalFile = "{$audioDir}/{$name}_{$tempoName}_01.mp3";

    //merge Command fuer ffmpeg (bis 06.23)
    //$mergeCommand = "ffmpeg -y -hide_banner -loglevel panic -i \"concat:{$preCountInFile}|{$countInFile}|{$mp3NormPath}\" -acodec copy {$finalFile}";

    //merge Command fuer mp3gain (ab 06.23)
    $mergeCommand = "ffmpeg -y -hide_banner -loglevel panic -i \"concat:{$preCountInFile}|{$countInFile}|{$mp3Path}\" -acodec copy {$finalFile}";
    shell_exec($mergeCommand);
  }
}

emptyDirectory("{$audioDir}/Temp");

function emptyDirectory($dir)
{
  $files = scandir($dir);
  foreach ($files as $file) {
    if ($file == '.' || $file == '..') {
      continue;
    }
    if (is_file("$dir/$file")) {
      unlink("$dir/$file");
    } elseif (is_dir("$dir/$file")) {
      emptyDirectory("$dir/$file");
      rmdir("$dir/$file");
    }
  }
}
