const rl = require('readline').createInterface({input: process.stdin})
let lines = []
rl.on('line', (line) => lines.push(line))
rl.on('close', () => {
    const chunkSize = 20
    const table = lines
            .map((line) => line.split(':'))
            .filter((entry) => entry.length >= 2)
            .map(([grade, chars]) => chars.split('').map((c) => `"${c}"=>"${grade}"`))
            .flat()
    const chunked = new Array(Math.ceil(table.length / chunkSize)).fill().map((_, i) => table.slice(i*chunkSize, (i+1)*chunkSize))
    const result = chunked.map((chunk) => chunk.join(',')).join(',\n')
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