const cents = value => Math.round(Math.max(0, Number(value) || 0) * 100 + 1e-7);

export function calculateLine({quantity, unitPrice, lineDiscount = 0, setupCharges = 0, printCharges = 0}) {
    const base = Math.max(0, Math.trunc(Number(quantity) || 0)) * cents(unitPrice);
    const discount = Math.min(base, cents(lineDiscount));
    return {
        baseAmount: base / 100,
        lineDiscount: discount / 100,
        lineTotal: (base + cents(setupCharges) + cents(printCharges) - discount) / 100,
    };
}

export function calculateTotal({lineTotals, discount = 0, setupCharges = 0, printCharges = 0}) {
    const subtotal = lineTotals.reduce((sum, line) => sum + cents(line), 0);
    const beforeDiscount = subtotal + cents(setupCharges) + cents(printCharges);
    const appliedDiscount = Math.min(beforeDiscount, cents(discount));
    return {subtotal: subtotal / 100, discount: appliedDiscount / 100, grandTotal: (beforeDiscount - appliedDiscount) / 100};
}
