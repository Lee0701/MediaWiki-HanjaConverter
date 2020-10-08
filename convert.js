
const hanjaReadings = require('./hanja_readings')
const readline = require('readline')
const rl = readline.createInterface({input: process.stdin})
const lines = []
rl.on('line', (line) => lines.push(line))
rl.on('close', () => {
    const commentLines = lines.filter((line => line.startsWith('#')))
    const dataLines = lines.filter((line) => !line.startsWith('#') && line.trim() !== '')
    
    const dict = {}
    dataLines.map((line) => line.split(':')).forEach(([reading, hanja, definition]) => {
        if(!dict[hanja]) dict[hanja] = []
        dict[hanja].push(reading)
    })

    const sortReadings = (hanja, readings) => readings.sort((a, b) => hanjaReadings[hanja] == a ? -1 : 1)
    
    Object.keys(dict).forEach((key) => {
        const values = dict[key]
        values.forEach((value, i) => {
            const decomposed = value.normalize('NFD')
            if(decomposed.charAt(decomposed.length - 1) != 'ᆺ') return
            const word = decomposed.slice(0, decomposed.length - 1).normalize('NFC')
            if(values.includes(word)) values.splice(i, 1)
        })
        if(key.length > 1) values.forEach((value, i) => {
            let decomposed = value.normalize('NFD')
            if(decomposed.charAt(0) == 'ᄅ') {
                decomposed = 'ᄂ' + decomposed.slice(1)
                const word = decomposed.normalize('NFC')
                if(values.includes(word)) values.splice(i, 1)
            }
            if(decomposed.charAt(0) == 'ᄂ') {
                decomposed = 'ᄋ' + decomposed.slice(1)
                const word = decomposed.normalize('NFC')
                if(values.includes(word)) values.splice(i, 1)
            }
        })
    })

    const result = Object.entries(dict)
            .map(([hanja, readings]) => [hanja, sortReadings(hanja, readings).join('/')])
            .map(([hanja, data]) => `"${hanja}" => "${data}"`)
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
})
