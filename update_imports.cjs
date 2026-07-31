const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

const search = `import { ref } from 'vue';`;
const replace = `import { ref, computed } from 'vue';`;

if (!content.includes('import { ref, computed }')) {
    content = content.replace(search, replace);
}

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
console.log('Update applied - imported computed');
