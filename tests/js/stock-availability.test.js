const { StockLookupError, formatAvailabilityText } = require('../../public/assets/js/stock-availability');

describe('formatAvailabilityText', () => {
    test('formats a single warehouse correctly', () => {
        const text = formatAvailabilityText({
            total: 20,
            per_warehouse: [{ warehouse_name: 'Jakarta Central Warehouse', quantity: 20 }],
        });

        expect(text).toBe('Available stock — Jakarta Central Warehouse: 20 (total: 20)');
    });

    test('joins multiple warehouses with a comma', () => {
        const text = formatAvailabilityText({
            total: 33,
            per_warehouse: [
                { warehouse_name: 'Jakarta Central Warehouse', quantity: 20 },
                { warehouse_name: 'Surabaya Branch Warehouse', quantity: 13 },
            ],
        });

        expect(text).toBe(
            'Available stock — Jakarta Central Warehouse: 20, Surabaya Branch Warehouse: 13 (total: 33)',
        );
    });

    test('handles zero stock in every warehouse (edge case)', () => {
        const text = formatAvailabilityText({
            total: 0,
            per_warehouse: [{ warehouse_name: 'Jakarta Central Warehouse', quantity: 0 }],
        });

        expect(text).toBe('Available stock — Jakarta Central Warehouse: 0 (total: 0)');
    });

    test('handles an empty warehouse list without throwing', () => {
        const text = formatAvailabilityText({ total: 0, per_warehouse: [] });

        expect(text).toBe('Available stock —  (total: 0)');
    });
});

describe('StockLookupError', () => {
    test('is a real Error subclass (instanceof both)', () => {
        const err = new StockLookupError('Product not found.', 'NOT_FOUND');

        expect(err).toBeInstanceOf(Error);
        expect(err).toBeInstanceOf(StockLookupError);
    });

    test('carries a machine-checkable code separate from the message', () => {
        const err = new StockLookupError('Product not found.', 'NOT_FOUND');

        expect(err.code).toBe('NOT_FOUND');
        expect(err.message).toBe('Product not found.');
        expect(err.name).toBe('StockLookupError');
    });
});
