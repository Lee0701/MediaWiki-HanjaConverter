
const fs = require('fs')

const dic2 = fs.readFileSync('dicts/dic2.txt').toString().split('\n')
const hanjaTxt = fs.readFileSync('dicts/hanja.txt').toString().split('\n')

const chunkSize = 10
const chunk = (arr) => new Array(Math.ceil(arr.length / chunkSize)).fill().map((_, i) => arr.slice(i*chunkSize, (i+1)*chunkSize))

const initialSoundLaw = (c) => {
    const normalized = c.normalize('NFD').split('')
    if(normalized[0] == 'ᄅ') normalized[0] = 'ᄂ'
    if(normalized[0] == 'ᄂ' && 'ᅣᅤᅧᅨᅭᅲᅵ'.includes(normalized[1])) normalized[0] = 'ᄋ'
    return normalized.join('').normalize('NFC')
}

const removeLast = (str) => str.normalize('NFD').split('').slice(0, -1).join('').normalize('NFC')


const commentLines = [['# (File: dic2.txt)', '#'], dic2, ['#', '# (File: hanja.txt)', '#'], hanjaTxt].flat()
        .filter((line => line.startsWith('#')))

const dic2Dict = dic2
        .filter((line) => !line.startsWith('#') && line.trim() !== '')
        .map((line) => line.split('\t')).filter((entry) => entry.length >= 2)
        .reduce((a, [hanja, reading]) => (a[hanja] = a[hanja] ? a[hanja] : reading.replace(/\s/g, ''), a), {})

const hanjaTxtDict = {}
hanjaTxt
        .filter((line) => !line.startsWith('#') && line.trim() !== '')
        .map((line) => line.split(':')).forEach(([reading, hanja]) => {
            if(!hanjaTxtDict[hanja]) hanjaTxtDict[hanja] = []
            hanjaTxtDict[hanja].push(reading)
        })

const table = Object.entries(hanjaTxtDict)
        .map(([hanja, readings]) => readings.map((reading) => [hanja, reading])).flat()
        .filter(([hanja, reading]) => hanja.length > 1 || dic2Dict[hanja] == reading)
        .filter(([hanja, reading]) => !hanjaTxtDict[hanja].includes(removeLast(reading))) // 사이시옷으로 끝나는 말 제거
        .filter(([hanja, reading]) => {
            if(hanja.length == 1) return true
            if(hanja.length != reading.length) return false
            if(hanja.split('').some((h, i) => {
                const r = reading.charAt(i)
                if(h == r) return false
                else if(hanjaTxtDict[h] && !(hanjaTxtDict[h].includes(r) || hanjaTxtDict[h].includes(removeLast(r)))) return truee
                else return false
            })) return false
            if(hanja.split('').every((h, i) => {
                const r = reading.charAt(i)
                if(h == r) return true
                else if(dic2Dict[h] == r) return true
                else return false
            })) return false
            return true
        })
        .sort(([_hanja, reading], [_hanja2, reading2]) => reading.localeCompare(reading2))
        .reduce((a, [hanja, reading]) => (a[hanja] = reading, a), {})

let filtered = Object.entries(table).filter(([hanja, reading]) => {

    let initial = true
    for(let i = 0; i < hanja.length;) {
        let found = false
        for(let j = hanja.length - i; j > 0; j--) {
            const key = hanja.slice(i, i + j)
            if(key == hanja) continue
            let value = table[key]
            if(!value) continue
            let slicedReading = reading.slice(i, i + j)
            // console.warn(key, value, slicedReading)
            if(value && initial) {
                value = initialSoundLaw(value)
                slicedReading = initialSoundLaw(slicedReading)
            }
            if(value == slicedReading) {
                i += j
                found = true
                break
            }
        }
        if(!found) return true
        initial = false
    }
    return false
})

const chunked = chunk(filtered.map(([hanja, reading]) => `"${hanja}"=>"${reading}"`))
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