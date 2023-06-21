# TipToi2023

Skripte mit Windows ausführen

count-in-Dateien müssen (zumindest für Tut-Video in Vegas) 44.1kHz, 128kbps sein, da Musescore CLI Export als mp3 44.1kHz, 128kbps hat (21.06.23)

php createAudioFiles.php (über Powershell, nicht parallelisiert)
createAudioFiles.js (über Deno parallelisiert)

Erzeugte Audiodateien aus Musescore-audio nach YAML/Audio schieben

php createYaml.php (über Powershell)
createYamls.ts (über Deno)