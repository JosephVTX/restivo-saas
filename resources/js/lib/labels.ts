/**
 * Spanish labels for the fixed tenant roles (spatie role names are kept as
 * technical identifiers: owner, admin, cashier, waiter, kitchen, member).
 */
const roleLabels: Record<string, string> = {
    owner: 'Propietario',
    admin: 'Administrador',
    cashier: 'Cajero',
    waiter: 'Mozo',
    kitchen: 'Cocinero',
    member: 'Miembro',
};

export function roleLabel(name?: string | null): string {
    if (!name) {
        return 'Miembro';
    }

    return roleLabels[name] ?? name;
}

/**
 * Spanish labels for the domain statuses. The values are kept as technical
 * identifiers in the database/APIs; only the UI translates.
 */
const statusLabels: Record<string, string> = {
    // Tenant / project
    draft: 'Borrador',
    active: 'Activo',
    trial: 'Prueba',
    archived: 'Archivado',
    suspended: 'Suspendido',
    cancelled: 'Cancelado',

    // Mesas
    available: 'Libre',
    occupied: 'Ocupada',
    billing: 'Por cobrar',
    reserved: 'Reservada',
    cleaning: 'Limpieza',

    // Pedidos
    open: 'Abierto',
    sent: 'Enviado',
    served: 'Servido',
    paid: 'Pagado',

    // Ítems de pedido / comandas
    pending: 'Pendiente',
    preparing: 'Preparando',
    ready: 'Listo',
    delivered: 'Entregado',
    void: 'Anulado',

    // Turnos de caja
    closed: 'Cerrado',

    // Comprobantes
    issued: 'Emitido',
    accepted: 'Aceptado',
    rejected: 'Rechazado',
    annulled: 'Anulado',
    none: 'Sin comprobante',
};

export function statusLabel(status?: string | null): string {
    if (!status) {
        return '—';
    }

    return statusLabels[status] ?? status;
}

/**
 * Payment method labels (values stay as technical identifiers).
 */
const paymentMethodLabels: Record<string, string> = {
    cash: 'Efectivo',
    yape: 'Yape',
    plin: 'Plin',
    card: 'Tarjeta',
    transfer: 'Transferencia',
    other: 'Otro',
};

export function paymentMethodLabel(method?: string | null): string {
    if (!method) {
        return '—';
    }

    return paymentMethodLabels[method] ?? method;
}

/**
 * Cash movement type labels (values stay as technical identifiers).
 */
const cashMovementTypeLabels: Record<string, string> = {
    in: 'Ingreso',
    out: 'Egreso',
};

export function cashMovementTypeLabel(type?: string | null): string {
    if (!type) {
        return '—';
    }

    return cashMovementTypeLabels[type] ?? type;
}

/**
 * Order type labels (values stay as technical identifiers).
 */
const orderTypeLabels: Record<string, string> = {
    dine_in: 'En mesa',
    takeaway: 'Para llevar',
    delivery: 'Delivery',
};

export function orderTypeLabel(type?: string | null): string {
    if (!type) {
        return '—';
    }

    return orderTypeLabels[type] ?? type;
}
