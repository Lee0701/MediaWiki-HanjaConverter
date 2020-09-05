
const readline = require('readline')
const rl = readline.createInterface({input: process.stdin})
const lines = []
rl.on('line', (line) => lines.push(line))
rl.on('close', () => {
    const dataLines = lines.filter((line) => !line.startsWith('#') && line.trim() !== '')
    
    const dict = {}
    dataLines.map((line) => line.split(':')).forEach(([reading, hanja, definition]) => {
        if(!dict[hanja]) dict[hanja] = []
        dict[hanja].push(reading)
    })

    const result = Object.entries(dict)
            .map(([hanja, arr]) => [hanja, arr.join('/')])
            .map(([hanja, data]) => `"${hanja}" => "${data}"`)
            .join(',\n')
    console.log(result)
})
