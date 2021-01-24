
const fs = require('fs')

const chunkSize = 10

const dic2 = fs.readFileSync('dicts/dic2.txt').toString().split('\n')
const hanjaTxt = fs.readFileSync('dicts/hanja.txt').toString().split('\n')
const krStdict = fs.readFileSync('dicts/kr-stdict.tsv').toString().split('\n')

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
        .filter(([hanja, reading]) => hanja.length == reading.length && !(hanja.length > 5 || hanja.match(/[가-힣ㄱ-ㅎㅏ-ㅣ]/)))
        .map(([hanja, reading]) => `"${hanja}"=>"${reading}"`)
const chunked = new Array(Math.ceil(table.length / chunkSize)).fill().map((_, i) => table.slice(i*chunkSize, (i+1)*chunkSize))
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