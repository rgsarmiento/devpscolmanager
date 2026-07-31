<?php
$file = '.env';
$content = file_get_contents($file);

// Clean encoding
$content = preg_replace('/[^\x20-\x7E\r\n\t]/', '', $content);

// 1. Local Database (MySQL)
$content = str_replace('DB_CONNECTION=sqlite', 'DB_CONNECTION=mysql', $content);
$content = str_replace('# DB_HOST=127.0.0.1', 'DB_HOST=127.0.0.1', $content);
$content = str_replace('# DB_PORT=3306', 'DB_PORT=3306', $content);
$content = str_replace('# DB_DATABASE=laravel', 'DB_DATABASE=devpscolmanager', $content);
$content = str_replace('# DB_USERNAME=root', 'DB_USERNAME=root', $content);
$content = str_replace('# DB_PASSWORD=', 'DB_PASSWORD=', $content);

// 2. Fix External Username typo if present
$content = str_replace('DB_EXTERNAL_USERNAME=robert', 'DB_EXTERNAL_USERNAME=rober', $content);

// 3. Ensure billing API and External DB are present (they seem partially there but let's be sure)
if (strpos($content, 'BILLING_API_URL') === false) {
    $content .= "\nBILLING_API_URL=http://api.devpscol.com:81/api/ubl2.1\n";
}

if (strpos($content, 'DB_EXTERNAL_HOST') === false) {
    $content .= "\n# Conexión externa para API Dian\n";
    $content .= "DB_EXTERNAL_HOST=3.137.97.32\n";
    $content .= "DB_EXTERNAL_PORT=3307\n";
    $content .= "DB_EXTERNAL_DATABASE=apidian_\n";
    $content .= "DB_EXTERNAL_USERNAME=robert\n";
    $content .= "DB_EXTERNAL_PASSWORD=Thanos141207\n";
}

file_put_contents($file, $content);
echo "Env restored successfully\n";
