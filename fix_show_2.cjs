const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

const modalIndex = content.indexOf('<!-- Modal for Computer -->');
if (modalIndex === -1) {
    console.error("Modal not found");
    process.exit(1);
}

// Insert two closing divs before the modal
const newContent = content.substring(0, modalIndex) + "            </div>\n        </div>\n\n        " + content.substring(modalIndex);

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', newContent);
console.log("Added the two closing divs back.");
