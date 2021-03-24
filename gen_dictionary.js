
const fs = require('fs')

const dic0Txt = fs.readFileSync('dicts/dic0.txt').toString().split('\n')
const dic2Txt = fs.readFileSync('dicts/dic2.txt').toString().split('\n')
const dic4Txt = fs.readFileSync('dicts/dic4.txt').toString().split('\n')

const CHUNK_SIZE = 10
const chunk = (arr, chunkSize=CHUNK_SIZE) => new Array(Math.ceil(arr.length / chunkSize)).fill().map((_, i) => arr.slice(i*chunkSize, (i+1)*chunkSize))

const buildMapDict = (lines) => lines
        .filter((line) => !line.startsWith('#') && line.trim() !== '')
        .map((line) => line.split('\t')).filter((entry) => entry.length >= 2)
        .reduce((a, [hanja, reading]) => (a[hanja] = a[hanja] ? a[hanja] : reading.replace(/\s/g, ''), a), {})

const initialSoundLaw = (c) => {
    const normalized = c.normalize('NFD').split('')
    if(normalized[0] == 'ᄅ') normalized[0] = 'ᄂ'
    if(normalized[0] == 'ᄂ' && 'ᅣᅤᅧᅨᅭᅲᅵ'.includes(normalized[1])) normalized[0] = 'ᄋ'
    return normalized.join('').normalize('NFC')
}

const dicTxt = [...dic0Txt, ...dic2Txt, ...dic4Txt]

const commentLines = dicTxt.filter((line => line.startsWith('#')))

const dict = buildMapDict(dicTxt)

Object.entries(dict).forEach(([hanja, reading]) => {
    if(hanja.length == 1) return true
    let allFound = true
    for(let i = 0; i < hanja.length;) {
        let found = false
        for(let j = hanja.length - i; j > 0; j--) {
            const key = hanja.slice(i, i + j)
            if(key == hanja) continue
            let slicedReading = reading.slice(i, i + j)
            const dictEntry = (i == 0) ? initialSoundLaw(dict[key] || '') : dict[key] || ''
            if(slicedReading == dictEntry) {
                i += j
                found = true
                break
            }
        }
        if(!found) {
            allFound = false
            i += 1
        }
        initial = false
    }
    if(allFound) delete dict[hanja]
})

const chunked = chunk(Object.entries(dict).map(([hanja, reading]) => `"${hanja}"=>"${reading}"`), 1)
const result = chunked.map((chunk) => chunk.join(',')).join(',\n')
const comment = commentLines.join('\n')
fs.writeFileSync('Dictionary.php', [
    '<?php',
    comment,
    'class Dictionary {',
    'public static $dictionary = array(',
    result,
    ');',
    '}',
    '?>'
].join('\n'))