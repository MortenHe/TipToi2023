import { unZipFromFile } from "https://deno.land/x/zip@v1.1.0/unzip.ts";
import { emptyDir } from "https://deno.land/std@0.74.0/fs/mod.ts";

// Dir for wav generation from Musescore notes
const audioDir = "C:/Users/Martin/Desktop/Nextcloud/TipToi2023/Musescore-audio";
const tempDir = audioDir + "/Temp";

await emptyDir(tempDir);

// Files for which audio is created
const names = [
  //"noten_lesen_01_1",
  //"noten_lesen_01_2",
  //"noten_lesen_01_3",
  //"noten_lesen_02_1",
  //"noten_lesen_02_2",
  //"noten_lesen_02_3",
  //"rhythmus_01_1",
  //"rhythmus_01_2",
  //"rhythmus_01_3",
  "song_01_wo_bist_du",
];

//TODO: nur Dateien erstellen, die noch nicht in Ordner
//TODO: expand ohne stdout

// List of tempos per Musescore file
const tempos = [
  // Tempo 60 => 60 / 60
  { name: "snail", value: 60, mult: 1 },
  // Tempo 70 => 70 / 60
  { name: "horse", value: 70, mult: 1.1666 },
  // Tempo 80 => 80 / 60
  { name: "cheetah", value: 80, mult: 1.3333 },
];
const timeSignature = "4_4";

// Empty Temp dir
//await emptyDirectory(tempDir);

//parallel audio file creation
const promises: any = [];
for (const name of names) {
  for (const tempo of tempos) {
    promises.push(createAudio(name, tempo));
  }
}
await Promise.all(promises);
console.log("creation done");

// Empty Temp dir
await emptyDir(tempDir);

//unzip xml, update tempo in xml, create wav, normalize wav, combine countin and wav file
async function createAudio(name, tempo) {
  //update xml tempo
  const zipPath = tempDir + "/" + name + "-" + tempo.value + ".zip";
  await Deno.copyFile(audioDir + "/" + name + ".mscz", zipPath);

  const xmlPath = tempDir + "/" + name + "-" + tempo.value;
  await unZipFromFile(zipPath, xmlPath);

  const mscxPath = xmlPath + "/" + name + ".mscx";

  const fileContent = await Deno.readTextFile(mscxPath);
  const modifiedFileContent = fileContent.replace(
    /<tempo>.*<\/tempo>/,
    `<tempo>${tempo.mult}</tempo>`,
  );
  await Deno.writeTextFile(mscxPath, modifiedFileContent);

  // wav generation from musescore file
  const wavPath = tempDir + "/" + name + "-" + tempo.value + ".wav";
  await Deno.run({
    cmd: [
      "cmd",
      "/c",
      "MuseScore4.exe",
      mscxPath,
      "-o",
      wavPath,
    ],
  }).status();

  // wav normalization
  const wavNormPath = tempDir + "/" + name + "-" + tempo.value + "_norm.wav";
  await Deno.run({
    cmd: [
      "cmd",
      "/c",
      "ffmpeg",
      "-y",
      "-hide_banner",
      "-loglevel",
      "panic",
      "-i",
      wavPath,
      "-filter:a",
      "volume=15dB",
      wavNormPath,
    ],
  }).status();

  // merge countInFile.wav + normalized musescore.wav
  const countInFile = audioDir + "/Count-in/" + tempo.value + "_" +
    timeSignature + ".wav";
  const concatFile = tempDir + "/" + name + "_" + tempo.name + ".wav";
  await Deno.run({
    cmd: [
      "ffmpeg",
      "-y",
      "-hide_banner",
      "-loglevel",
      "panic",
      "-i",
      countInFile,
      "-i",
      wavNormPath,
      "-filter_complex",
      "[0:0][1:0]concat=n=2:v=0:a=1[out]",
      "-map",
      "[out]",
      "-c:a",
      "pcm_s24le",
      concatFile,
    ],
  }).status();

  // wav to mp3 conversion
  const finalFile = audioDir + "/" + name + "_" + tempo.name + "_01.mp3";
  await Deno.run({
    cmd: [
      "cmd",
      "/c",
      "ffmpeg",
      "-y",
      "-hide_banner",
      "-loglevel",
      "panic",
      "-i",
      concatFile,
      "-vn",
      "-ar",
      44100,
      "-ac",
      2,
      "-b:a",
      "128k",
      finalFile,
    ],
  }).status();
}
