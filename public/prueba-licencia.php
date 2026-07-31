<?php

class LicenseService
{
    private const SECRET_KEY = '$N0D0$';

    private static function getDesKey(string $pin = ''): string
    {
        $keyStr = $pin . self::SECRET_KEY;
        $md5Hash = md5($keyStr, true);
        return $md5Hash . substr($md5Hash, 0, 8);
    }

    public static function encrypt(string $stringToEncrypt, string $pin = ''): string
    {
        $key = self::getDesKey($pin);
        $encrypted = openssl_encrypt($stringToEncrypt, 'des-ede3-ecb', $key, OPENSSL_RAW_DATA);
        return base64_encode($encrypted);
    }

    public static function decrypt(string $encryptedString, string $pin = ''): string
    {
        try {
            $key = self::getDesKey($pin);
            $decoded = base64_decode($encryptedString);
            $decrypted = openssl_decrypt($decoded, 'des-ede3-ecb', $key, OPENSSL_RAW_DATA);
            return $decrypted !== false ? $decrypted : 'na';
        } catch (\Exception $e) {
            return 'na';
        }
    }
}

// Variables para el formulario
$pinStringInput = $_POST['txtPinString'] ?? '';
$fechaInput = $_POST['fecha'] ?? date('Y-m-d', strtotime('+1 year')); // Por defecto +1 año como en VB
$licenciaGenerada = '';
$empresaDesencriptada = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($pinStringInput)) {
    // 1. Extraer el PIN y la Empresa del string (separado por espacio)
    $partes = explode(' ', trim($pinStringInput), 2);
    
    if (count($partes) >= 2) {
        $pin = $partes[0];
        $empresaEncriptada = $partes[1];
        
        // 2. Desencriptar Empresa
        $empresaDesencriptada = LicenseService::decrypt($empresaEncriptada, $pin);
        
        // 3. Generar Licencia con la fecha
        // Tu VB.NET usa el formato "dd-MM-yyyy"
        $fechaFormateada = date('d-m-Y', strtotime($fechaInput));
        $licenciaGenerada = LicenseService::encrypt($fechaFormateada, $pin);
    } else {
        $empresaDesencriptada = "Error: Falta el espacio. El formato debe ser 'PIN EMPRESA_ENCRIPTADA'";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>DevpsCol License Generator (PHP)</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1e1e1e; color: #fff; padding: 40px; }
        .container { background: #2d2d30; padding: 25px; border-radius: 8px; max-width: 500px; margin: 0 auto; border: 1px solid #007acc; box-shadow: 0 10px 20px rgba(0,0,0,0.5); }
        h2 { color: #fff; margin-top: 0; font-size: 1.2rem; margin-bottom: 20px; }
        label { display: block; margin-top: 15px; color: #ccc; font-size: 0.9rem; margin-bottom: 5px;}
        input[type="text"], input[type="date"] { width: 100%; box-sizing: border-box; padding: 10px; background: #3e3e42; border: 1px solid #555; color: #fff; border-radius: 4px; }
        button { background: #007acc; color: white; border: none; padding: 12px; margin-top: 20px; cursor: pointer; border-radius: 4px; font-weight: bold; width: 100%; font-size: 1rem; }
        button:hover { background: #005a9e; }
        .result { background: #1e1e1e; padding: 15px; margin-top: 25px; border-radius: 4px; border-left: 4px solid #007acc; }
        .result-input { font-family: monospace; font-size: 1.1rem; color: #00ff00; }
    </style>
</head>
<body>

<div class="container">
    <h2>DevpsCol License Generator</h2>
    
    <form method="POST">
        <label>txtPinString (Ej: 12345 j2G/a...)</label>
        <input type="text" name="txtPinString" value="<?php echo htmlspecialchars($pinStringInput); ?>" placeholder="Pega aquí el string completo..." required autofocus>
        
        <label>Fecha de Vencimiento:</label>
        <input type="date" name="fecha" value="<?php echo htmlspecialchars($fechaInput); ?>" required>
        
        <button type="submit">Generar Licencia</button>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <div class="result">
        <strong>Empresa:</strong><br>
        <span style="color: silver;"><?php echo htmlspecialchars($empresaDesencriptada); ?></span><br><br>
        
        <strong>Licencia Generada:</strong><br>
        <input type="text" class="result-input" value="<?php echo htmlspecialchars($licenciaGenerada); ?>" readonly onclick="this.select(); document.execCommand('copy'); alert('¡Licencia copiada!');">
    </div>
    <?php endif; ?>
</div>

</body>
</html>