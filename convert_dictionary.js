
const hanjaGrades = require('./hanja_grades')
const fs = require('fs')
const contents = fs.readdirSync('dicts').map((name) => fs.readFileSync(`dicts/${name}`).toString())
const lines = contents.map((content) => content.split('\n')).flat()

const commentLines = lines.filter((line => line.startsWith('#')))
const dataLines = lines.filter((line) => !line.startsWith('#') && line.trim() !== '')

const dict = dataLines.map((line) => line.split('\t'))
        .reduce((a, [hanja, reading]) => (a[hanja] = a[hanja] ? a[hanja] : reading.replace(/\s/g, ''), a), {})

const grade = (hanja) => hanja.split('').filter((c) => !(c >= '가' && c <= '힣' || c >= 'ㄱ' && c <= 'ㅣ')).map((c) => {
    const grade = Object.entries(hanjaGrades).find(([_grade, list]) => list.indexOf(c) != -1)
    if(grade === undefined) return 0
    else return parseInt(grade[0])
}).reduce((a, c) => a < c ? a : c)

const result = Object.entries(dict)
        .map(([hanja, reading]) => `"${hanja}" => "${reading}:${grade(hanja)}"`)
        .join(',\n')
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