const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Clients/Show.vue', 'utf8');

const search = `<!-- STEP 2 & 4 (Software & Cert) -->
                            <div class="space-y-12" :class="{ 'opacity-40 select-none grayscale': !client.invoicing_info?.api_token }">`;
const replace = `<!-- STEP 2 & 4 (Software & Cert) -->
                            <div class="md:col-span-2 space-y-12" :class="{ 'opacity-40 select-none grayscale': !client.invoicing_info?.api_token }">`;

const search2 = `<!-- STEP 3 (Resolutions) -->
                            <div class="space-y-12" :class="{ 'opacity-40 select-none grayscale': !client.invoicing_info?.api_token }">`;
const replace2 = `<!-- STEP 3 (Resolutions) -->
                            <div class="md:col-span-2 space-y-12" :class="{ 'opacity-40 select-none grayscale': !client.invoicing_info?.api_token }">`;

content = content.replace(search, replace);
content = content.replace(search2, replace2);

fs.writeFileSync('resources/js/Pages/Clients/Show.vue', content);
console.log('Update applied');
