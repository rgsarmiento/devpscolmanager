const changelogData = [
    {
        version: "2.1.0",
        date: "2026-09-14",
        module: "Facturación",
        changes: [
            { type: "feature", text: "Nueva vista centralizada para configuración de servidores SMTP (Preestablecidos)." },
            { type: "improvement", text: "Optimización de velocidad en la carga de la vista del cliente eliminando consultas pesadas asincrónicas." },
            { type: "fix", text: "Corrección en el filtrado de certificados usando el NIT en la vista global." }
        ]
    },
    {
        version: "2.0.5",
        date: "2026-09-09",
        module: "Dashboard",
        changes: [
            { type: "feature", text: "Añadida columna de 'Password' en los certificados próximos a vencer con botón de visualización." },
            { type: "improvement", text: "Se migró la fuente de datos de certificados directamente a la base de datos externa." }
        ]
    },
    {
        version: "2.0.0",
        date: "2026-08-20",
        module: "Core / POS",
        changes: [
            { type: "breaking", text: "Actualización de motor de base de datos. Requiere migración." },
            { type: "feature", text: "Nuevo módulo de 'Saldos Pendientes' para distribuidores." },
            { type: "improvement", text: "Soporte responsivo (Móvil) para las tablas de historial de cobros." },
            { type: "fix", text: "Se resolvió un bug que impedía subir imágenes desde el portapapeles (Ctrl+V)." }
        ]
    }
];
