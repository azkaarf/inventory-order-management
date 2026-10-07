(function (root) {
    'use strict';

    class StockLookupError extends Error {
        constructor(message, code) {
            super(message);
            this.name = 'StockLookupError';
            this.code = code;
        }
    }

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
