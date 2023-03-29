// Dir for wav generation from Musescore notes
const yamlDir = "C:/Users/Martin/Desktop/Nextcloud/TipToi2023/YAML";

//ttt Befehle im yaml Ordner ausfuehren,
Deno.chdir(yamlDir);

//YAML file fuer GME Erstellung
const outputName = "notenbuch.yaml";

//Headerbereich der YAML-Datei
let output = `product-id: 925
comment: \"Notenbuch von Martin Helfer\"
welcome: start, welcome
gme-lang: GERMAN
media-path: Audio/%s`;

//YAML config
type CodeRow = [string, number?, string?];
type Block = {
  header: string;
  codes: CodeRow[];
};
const blocks: Block[] = [{
  header: "01-das-klavier",
  codes: [
    ["klavier", 5, "single"],
    ["glissando"],
    ["klavier_tief", 2],
    ["klavier_mittel", 2],
    ["klavier_hoch", 2],
    ["keys", 3],
    ["der_ton_c"],
    ["draw_c_und_d", 2],
    ["words_with_c"],
    ["der_ton_d"],
    ["words_with_d"],
  ],
}, {
  header: "02-die-notenschrift",
  codes: [
    ["notenschrift", 4, "single"],
    ["viertelnote", 2],
    ["notenlinien", 3],
    ["notenlinien_mit_noten", 2],
    ["notenlinien_mit_allem", 2],
    ["noten_c_und_d"],
    ["draw_noten_c_und_d", 2],
  ],
}, {
  header: "03-noten-lesen-01",
  codes: [
    ["uebung_01_explain", 2],
    ["faster_than_cheetah", 2],
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
}, {
  header: "04-der-ton-e",
  codes: [
    ["der_ton_e"],
    ["draw_e", 2],
    ["words_with_e"],
    ["recognize_notes_e", 2],
    ["write_notes_e"],
  ],
}, {
  header: "05-noten-lesen-02",
  codes: [
    ["uebung_02_explain"],
    ["fingers_02_explain", 2],
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
}, {
  header: "06-viertelpause",
  codes: [
    ["viertelpause"],
    ["make_a_break", 2],
    ["rest_01_explain", 2],

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
}, {
  header: "07-wo-bist-du",
  codes: [
    ["song_01_explain", 2],
    ["wo_bist_du", 2],
    ["summary_01_explain"],
    ["summary_piano"],
    ["summary_c", 2],
    ["summary_d", 2],
    ["summary_e", 2],
    ["summary_viertelnote"],
    ["summary_viertelpause"],
    ["summary_notenlinien"],
    ["summary_violinschluessel"],
    ["summary_vier_viertel_takt"],
    ["summary_01_fingersatz"],
    ["summary_notenlinie_komplett", 2],

    ["song_01_wo_bist_du_snail"],
    ["song_01_wo_bist_du_horse"],
    ["song_01_wo_bist_du_cheetah"],
  ],
}];

//Inits fuer Scripte mit mehreren Soundfiles pro Code
const inits = [];

//Scripts erstellen
const scripts = [];

//YAML aus Config erstellen
for (const block of blocks) {
  //Kommentar in welchem Block wir sind: #01-das-klavier
  scripts.push("\n  #" + block.header);

  //Codes pro Block erzeugen
  for (const code of block.codes) {
    const name = code[0];
    const count = code[1] ?? null;

    //Einzelner Sound: code: P(code_01)
    if (!count) {
      const single_code = "  " + name + ": P(" + name + "_01)";
      scripts.push(single_code);
    } else {
      const mode = code[2] ?? "multi";
      switch (mode) {
        //Mehrere Soundfiles nacheinander: code: P(code_01) P(code_02) P(code_03)
        case "single": {
          let single_line = "  " + name + ":";
          for (let i = 1; i <= count; i++) {
            single_line += " P(" + name + "_0" + i + ")";
          }
          scripts.push(single_line);
          break;
        }

        //Durch Soundfiles gehen bei mehrmaligem Tippen:  keys: \n- $keys==0? P(keys_01) $keys+=1 $keys%=3
        case "multi": {
          inits.push("$" + name + ":=0");
          let multiline = "  " + name + ":\n";
          for (let i = 1; i <= count; i++) {
            multiline += "   - $" + name + "==" + (i - 1) + "? P(" + name +
              "_0" + i + ") $" + name + "+=1 $" + name + "%=" + count;
            if (i < count) {
              multiline += "\n";
            }
          }
          scripts.push(multiline);
          break;
        }
      }
    }
  }
}

//YAML-Datei schreiben
output += "\ninits: " + inits.join(" ");
output += "\nscripts:\n";
output += scripts.join("\n");
await Deno.writeTextFile(outputName, output);

// Get a list of all the files in the directory
/*
const files = await Deno.readDir(yamlDir);

// Loop through each file and delete any file that ends with ".png"
for await (const file of files) {
  if (file.isFile && file.name.endsWith(".png")) {
    // await Deno.remove(yamlDir + "/" + file.name);
  }
}
*/

//Codes erstellen
await Deno.run({
  cmd: [
    "tttool.exe",
    "oid-codes",
    outputName,
    "--pixel-size",
    "4",
    "--code-dim",
    "15",
  ],
}).status();

// move png to Codes folder
for await (const file of Deno.readDir(yamlDir)) {
  if (file.isFile && file.name.endsWith(".png")) {
    const newFilename = file.name.replace("oid-", "");
    const newPath = `${yamlDir}/Codes/${newFilename}`;
    await Deno.rename(`${yamlDir}/${file.name}`, newPath);
  }
}

//GME erstellen
await Deno.run({
  cmd: [
    "tttool.exe",
    "assemble",
    outputName,
  ],
}).status();
