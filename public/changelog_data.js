const changelogData = [
    {
        version: "7.14.9.26",
        date: "2026-09-14",
        module: "Nodo POS",
        changes: [
            { type: "feature", text: "Cartera: Nuevo sistema de distribución inteligente (FIFO) para abonos múltiples. El pago se reparte automáticamente desde la factura más antigua a la más reciente indicando saldos restantes." },
            { type: "feature", text: "Facturación Electrónica: Ahora es posible reasignar clientes a facturas físicas emitidas a 'Consumidor Final' de forma retroactiva, justo antes de convertirlas en electrónicas." },
            { type: "feature", text: "Sincronización: Se agregó la capacidad de transmitir y actualizar el catálogo de 'Usuarios del Sistema' hacia todas las cajas conectadas en la red local." },
            { type: "feature", text: "Caja: Nuevo acceso/botón rápido para abrir el cajón monedero directamente desde el módulo de Ingresos/Egresos." },
            { type: "improvement", text: "Cartera: Rediseño de la interfaz de Créditos de Ventas (UX/UI). Incorpora resaltado de filas para deudas vencidas, ajuste dinámico de columnas y limpieza rápida del filtro de clientes." },
            { type: "improvement", text: "Inventario: Nuevo reporte de 'Productos por agotarse', con generación nativa de PDF (diseño limpio y resaltado en rojo) y exportación a Excel." },
            { type: "improvement", text: "Seguridad: Transición a un modelo estricto de 'Lista Blanca' para permisos de roles operativos." },
            { type: "fix", text: "Facturación: Corrección al generar notas de crédito; ahora se relaciona correctamente la llave con el documento de origen." },
            { type: "improvement", text: "Reportes: Se ajustó el reporte de cantidad de productos, añadiendo soporte para filtrar dinámicamente por Marcas y Categorías." }
        ]
    }
];


