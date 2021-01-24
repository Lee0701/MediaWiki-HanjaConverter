const rl = require('readline').createInterface({input: process.stdin})
let lines = []
rl.on('line', (line) => lines.push(line))
rl.on('close', () => {
    const result = lines
            .map((line) => line.split(':'))
            .filter((entry) => entry.length >= 2)
            .map(([grade, chars]) => chars.split('').map((c) => `"${c}"=>"${grade}"`))
            .flat()
            .join(',\n')
    console.log([
        '<?php',
        'class GradesDictionary {',
        'public static $dictionary = array(',
        result,
        ');',
        '}',
        '?>'
    ].join('\n'))
})