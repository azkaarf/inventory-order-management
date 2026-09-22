/**
 * Bab 8.1: pure/testable logic dipisah dari DOM & fetch (yang tinggal di
 * inline script sales-orders/show.php). File ini nggak menyentuh document,
 * window.fetch, atau apapun yang butuh browser - jadi bisa di-require
 * langsung dari Jest tanpa mock DOM sama sekali.
 */
(function (root) {
    'use strict';

    // Bab 6.2: custom error yang extend Error bawaan, dengan `code` supaya
    // caller bisa membedakan jenis kegagalan tanpa parsing pesan teks.
    class StockLookupError extends Error {
        constructor(message, code) {
            super(message);
            this.name = 'StockLookupError';
            this.code = code;
        }
    }

    /**
     * Bab 1.2: destructuring dipakai di parameter langsung. Fungsi ini
     * murni transformasi data -> string, nggak ada side effect, sehingga
     * gampang diuji dengan input apapun tanpa perlu network/DOM asli.
     */
    function formatAvailabilityText({ total, per_warehouse: perWarehouse }) {
        const parts = perWarehouse.map(({ warehouse_name: name, quantity }) => `${name}: ${quantity}`);
        return `Available stock — ${parts.join(', ')} (total: ${total})`;
    }

    const api = { StockLookupError, formatAvailabilityText };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = api;
    }
    if (root) {
        root.StockAvailability = api;
    }
})(typeof window !== 'undefined' ? window : undefined);
