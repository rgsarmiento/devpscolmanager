const fs = require('fs');
const files = ['resources/js/Pages/Clients/Show.vue', 'resources/js/Pages/Computers/Index.vue'];

const replacements = {
    'Ã¡': 'á',
    'Ã©': 'é',
    'Ã\xAD': 'í',
    'Ã³': 'ó',
    'Ãº': 'ú',
    'Ã±': 'ñ',
    'Ã‘': 'Ñ',
    'Ã\x81': 'Á',
    'Ã\x89': 'É',
    'Ã\x8D': 'Í',
    'Ã\x93': 'Ó',
    'Ã\x9A': 'Ú',
    'â†’': '→',
    'â†‘': '↑',
    'â†“': '↓',
    'Â¿': '¿'
};

files.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    for (const [bad, good] of Object.entries(replacements)) {
        content = content.split(bad).join(good);
    }
    fs.writeFileSync(file, content, 'utf8');
});
console.log('Fixed encoding issues');
