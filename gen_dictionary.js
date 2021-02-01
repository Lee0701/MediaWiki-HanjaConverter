
const fs = require('fs')

const dic2Txt = fs.readFileSync('dicts/dic2.txt').toString().split('\n')
const hanjaTxt = fs.readFileSync('dicts/hanja.txt').toString().split('\n')
const overrideTxt = fs.readFileSync('dicts/override.txt').toString().split('\n')

const CHUNK_SIZE = 10
const chunk = (arr, chunkSize=CHUNK_SIZE) => new Array(Math.ceil(arr.length / chunkSize)).fill().map((_, i) => arr.slice(i*chunkSize, (i+1)*chunkSize))

const initialSoundLaw = (c) => {
    const normalized = c.normalize('NFD').split('')
    if(normalized[0] == 'ᄅ') normalized[0] = 'ᄂ'
    if(normalized[0] == 'ᄂ' && 'ᅣᅤᅧᅨᅭᅲᅵ'.includes(normalized[1])) normalized[0] = 'ᄋ'
    return normalized.join('').normalize('NFC')
}

const removeLast = (str) => str.normalize('NFD').split('').slice(0, -1).join('').normalize('NFC')


const commentLines = [['# (File: dic2.txt)', '#'], dic2Txt, ['#', '# (File: hanja.txt)', '#'], hanjaTxt].flat()
        .filter((line => line.startsWith('#')))

const dic2Dict = dic2Txt
        .filter((line) => !line.startsWith('#') && line.trim() !== '')
        .map((line) => line.split('\t')).filter((entry) => entry.length >= 2)
        .reduce((a, [hanja, reading]) => (a[hanja] = a[hanja] ? a[hanja] : reading.replace(/\s/g, ''), a), {})

const overrideDict = overrideTxt
        .filter((line) => !line.startsWith('#') && line.trim() !== '')
        .map((line) => line.split(':')).filter((entry) => entry.length >= 2)
        .reduce((a, [hanja, reading]) => (a[hanja] = reading, a), {})

const dict = {}
// Add entries from hanja.txt
hanjaTxt
        .filter((line) => !line.startsWith('#') && line.trim() !== '')
        .map((line) => line.split(':'))
        .forEach(([reading, hanja]) => {
            if(!dict[hanja]) dict[hanja] = []
            dict[hanja].push(reading)
        })
// Add missing entries from dic2
Object.entries(dic2Dict).forEach(([hanja, reading]) => {
    if(!dict[hanja]) dict[hanja] = [reading]
    else if(!dict[hanja].includes(reading)) dict[hanja].push(reading)
})

// Check if every characters' reading is valid in itself
const checkReading = (hanja, reading) => {
    const duplicate = dict[hanja] && dict[hanja].length > 1
    if(overrideDict[hanja]) return overrideDict[hanja] == reading
    return hanja.split('').every((h, i) => {
        const r = reading.charAt(i)
        if(h == r) return true
        // Adding initial sound law for every character allows most of common pronunciation (like 困難:곤란)
        if(duplicate) return dic2Dict[h] == r || dic2Dict[h] && initialSoundLaw(dic2Dict[h]) == initialSoundLaw(r)
        else return !dict[h] || dict[h].includes(r) || dict[h].map((e) => initialSoundLaw(e)).includes(initialSoundLaw(r))
    })
}

// Check duplicate entries for every possible combinations
const checkDuplicate = (hanja, reading) => {
    if(hanja.length == 1) return true
    if(hanja.length != reading.length) return false
    if(hanja.split('').every((h, i) => {
        const r = reading.charAt(i)
        if(h == r) return true
        else if(dic2Dict[h] != r) return false
        else if(dict[h] && dict[h].includes(r)) return true
        else return false
    })) return false
    let initial = true
    let allFound = true
    for(let i = 0; i < hanja.length;) {
        let found = false
        for(let j = hanja.length - i; j > 0; j--) {
            const key = hanja.slice(i, i + j)
            if(key == hanja) continue
            let slicedReading = reading.slice(i, i + j)
            let values = dict[key]
            if(j == 1) values = dic2Dict[key] ? [dic2Dict[key]] : []
            if(key == slicedReading) values = [key]
            if(initial && values && values.length) {
                values = values.map((v) => initialSoundLaw(v))
                slicedReading = initialSoundLaw(slicedReading)
            }
            if(values && values.includes(slicedReading)) {
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
    if(!allFound) return true
    return false
}

const table = Object.entries(dict)
        .map(([hanja, readings]) => readings.map((reading) => [hanja, reading])).flat()
        .filter(([hanja, reading]) => dic2Dict[hanja] == reading || hanja.length > 1)
        .filter(([hanja, reading]) => checkDuplicate(hanja, reading))
        .filter(([hanja, reading]) => checkReading(hanja, reading))
        .sort(([_hanja, reading], [_hanja2, reading2]) => reading.localeCompare(reading2))

const map = Object.entries(table.reduce((a, [hanja, reading]) => (a[hanja] = reading, a), {}))

const chunked = chunk(map.map(([hanja, reading]) => `"${hanja}"=>"${reading}"`), 1)
const result = chunked.map((chunk) => chunk.join(',')).join(',\n')
const comment = commentLines.join('\n')
console.log([
    '<?php',
    comment,
    'class Dictionary {',
    'public static $dictionary = array(',
    result,
    ');',
    '}',
    '?>'
].join('\n'))