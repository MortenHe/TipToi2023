const decompress = require("decompress");
const fsPromises = require('fs').promises;
const path = require('path');
const util = require('util');
const exec = util.promisify(require('node:child_process').exec);

// Dir for wav generation from Musescore notes
const audioDir = "C:/Users/Martin/Desktop/Nextcloud/TipToi2023/Musescore-audio";
const tempDir = audioDir + "/Temp";

// Files for which audio is created
const names = [
    "noten_lesen_01_1",
    "noten_lesen_01_2",
    "noten_lesen_01_3",
];

// List of tempos per Musescore file
const tempos = [
    // Tempo 60 => 60 / 60
    { name: "snail", value: 60, mult: 1 },
    // Tempo 70 => 70 / 60
    { name: "horse", value: 70, mult: 1.1666 },
    // Tempo 80 => 80 / 60
    { name: "cheetah", value: 80, mult: 1.3333 }
];
const timeSignature = "4_4";

async function start() {

    // Empty Temp dir
    await emptyDirectory(tempDir);

    //parallel audio file creation
    const promises = [];
    for (const name of names) {
        for (const tempo of tempos) {
            promises.push(createAudio(name, tempo));
        }
    }
    await Promise.all(promises);
    console.log("creation done");

    // Empty Temp dir
    //emptyDirectory(tempDir);
}

//unzip xml, update tempo in xml, create wav, normalize wav, combine countin and wav file
async function createAudio(name, tempo) {

    //unzip xml
    const xmlPath = tempDir + "/" + name + "-" + tempo.value;
    await decompress(audioDir + "/" + name + ".mscz", xmlPath);

    //update xml tempo
    const mscxPath = xmlPath + "/" + name + ".mscx";
    const fileContent = await fsPromises.readFile(mscxPath, 'utf8');
    const modifiedFileContent = fileContent.replace(/<tempo>.*<\/tempo>/, `<tempo>${tempo.mult}</tempo>`);
    await fsPromises.writeFile(mscxPath, modifiedFileContent);

    // wav generation from musescore file
    const wavPath = tempDir + "/" + name + "-" + tempo.value + ".wav";
    await exec(`MuseScore4.exe "${mscxPath}" -o "${wavPath}"`);

    // wav normalization
    const wavNormPath = tempDir + "/" + name + "-" + tempo.value + "_norm.wav"
    //await exec(`ffmpeg -y -hide_banner -loglevel panic -i "${wavPath}" -af loudnorm -ar 44100 "${wavNormPath}"`);
    await exec(`ffmpeg -y -hide_banner -loglevel panic -i "${wavPath}" -filter:a "volume=15dB" "${wavNormPath}"`);

    // merge countInFile.wav + normalized musescore.wav
    const countInFile = audioDir + "/Count-in/" + tempo.value + "_" + timeSignature + ".wav";
    const concatFile = tempDir + "/" + name + "_" + tempo.name + ".wav";
    //await exec(`ffmpeg -y -hide_banner -loglevel panic -i "concat:${countInFile}|${wavNormPath}" -acodec copy "${concatFile}"`);
    await exec(`ffmpeg -y -hide_banner -loglevel panic -i ${countInFile} -i ${wavNormPath} -filter_complex "[0:0][1:0]concat=n=2:v=0:a=1[out]" -map "[out]" -c:a pcm_s24le ${concatFile}`);

    // wav to mp3 conversion
    const finalFile = audioDir + "/" + name + "_" + tempo.name + "_01.mp3";
    await exec(`ffmpeg -y -hide_banner -loglevel panic -i ${concatFile} -vn -ar 44100 -ac 2 -b:a 128k ${finalFile}`);
}

// Function to empty a directory
async function emptyDirectory(dir) {
    const files = await fsPromises.readdir(dir);
    for (const file of files) {
        const filePath = path.join(dir, file);
        if ((await fsPromises.lstat(filePath)).isDirectory()) {
            await emptyDirectory(filePath);
            await fsPromises.rmdir(filePath);
        } else {
            await fsPromises.unlink(filePath);
        }
    }
}

// start async script
start();