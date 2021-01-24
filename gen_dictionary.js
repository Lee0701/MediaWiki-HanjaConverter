
const fs = require('fs')

const dic2 = fs.readFileSync('dicts/dic2.txt').toString().split('\n')
const hanjaTxt = fs.readFileSync('dicts/hanja.txt').toString().split('\n')
const krStdict = fs.readFileSync('dicts/kr-stdict.tsv').toString().split('\n')

const chunkSize = 10
const chunk = (arr) => new Array(Math.ceil(arr.length / chunkSize)).fill().map((_, i) => arr.slice(i*chunkSize, (i+1)*chunkSize))

const commentLines = [dic2, hanjaTxt, krStdict].flat()
        .filter((line => line.startsWith('#')))

const dict = [dic2, krStdict].flat()
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

Object.entries(dict).forEach(([key, value]) => {
    if(hanjaTxtDict[key] && !hanjaTxtDict[key].includes(value)) dict[key] = hanjaTxtDict[key][0]
})

const table = Object.entries(dict)
        .sort(([_hanja, reading], [_hanja2, reading2]) => reading.localeCompare(reading2))
        .filter(([hanja, reading]) => hanja.length == reading.length && !(hanja.match(/[가-힣ㄱ-ㅎㅏ-ㅣ]/)))
        .reduce((a, [hanja, reading]) => (a[hanja] = reading, a), {})

const filtered = Object.entries(table).filter(([hanja, reading]) => {
    if(hanja.length == 1) return true
    for(let i = 0; i < hanja.length;) {
        let found = false
        for(let j = hanja.length - i; j > 0; j--) {
            const key = hanja.slice(i, i + j)
            if(key == hanja) continue
            const value = table[key]
            // console.log(key, value, reading.slice(i, i + j))
            if(value == reading.slice(i, i + j)) {
                i += j
                found = true
                break
            }
        }
        if(!found) return true
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