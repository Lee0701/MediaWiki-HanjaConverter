
const hanjaGrades = require('./hanja_grades')
const fs = require('fs')
const contents = fs.readdirSync('dicts').map((name) => fs.readFileSync(`dicts/${name}`).toString())
const lines = contents.map((content) => content.split('\n')).flat()

const commentLines = lines.filter((line => line.startsWith('#')))
const dataLines = lines.filter((line) => !line.startsWith('#') && line.trim() !== '')

const dict = dataLines.map((line) => line.split('\t')).filter((entry) => entry.length >= 2)
        .reduce((a, [hanja, reading]) => (a[hanja] = a[hanja] ? a[hanja] : reading.replace(/\s/g, ''), a), {})

const grade = (hanja) => hanja.split('').filter((c) => c.replace(/[가-힣ㄱ-ㅎㅏ-ㅣ]/g, '')).map((c) => {
    const grade = Object.entries(hanjaGrades).find(([_grade, list]) => list.indexOf(c) != -1)
    if(grade === undefined) return 0
    else return parseInt(grade[0])
})

const minGrade = (hanja) => grade(hanja).reduce((a, c) => a < c ? a : c)
const maxGrade = (hanja) => grade(hanja).reduce((a, c) => a > c ? a : c)

const result = Object.entries(dict)
        .sort(([_hanja, reading], [_hanja2, reading2]) => reading.localeCompare(reading2))
        .filter(([hanja, reading]) => !(maxGrade(hanja) == 0 && hanja.length > 1 || hanja.length > 5 || hanja.match(/[가-힣ㄱ-ㅎㅏ-ㅣ]/)))
        .map(([hanja, reading, grade]) => `"${hanja}"=>"${reading}:${minGrade(hanja)}"`)
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