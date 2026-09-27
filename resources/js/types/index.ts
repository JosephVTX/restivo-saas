export type ProjectStatus = 'draft' | 'active' | 'archived';
export type TenantStatus = 'active' | 'trial' | 'suspended' | 'cancelled';
export type RoleName = 'owner' | 'admin' | 'member';

export interface User {
    uuid: string;
    name: string;
    email: string;
    is_super_admin: boolean;
    is_active: boolean;
    locale: string;
    last_login_at: string | null;
    roles?: string[];
    memberships_count?: number;
    created_at: string;
}

export interface Tenant {
    uuid: string;
    name: string;
    slug: string;
    status: TenantStatus;
    status_label: string;
    plan: string | null;
    locale: string;
    settings: Record<string, unknown> | null;
    trial_ends_at: string | null;
    suspended_at: string | null;
    members_count?: number;
    projects_count?: number;
    created_at: string;
}

export interface Project {
    uuid: string;
    name: string;
    slug: string;
    description: string | null;
    status: ProjectStatus;
    status_label: string;
    meta: Record<string, unknown> | null;
    due_date: string | null;
    created_at: string;
    updated_at: string;
}

export interface Membership {
    uuid: string;
    job_title: string | null;
    joined_at: string | null;
    user: User;
    roles: string[];
}

export type TableStatus = 'available' | 'occupied' | 'billing' | 'reserved' | 'cleaning';
export type Station = 'kitchen' | 'bar' | 'none';
export type TaxType = 'gravado' | 'exonerado' | 'inafecto';
export type ModifierSelectionType = 'single' | 'multiple';

export interface Zone {
    uuid: string;
    name: string;
    sort_order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface DiningTable {
    uuid: string;
    zone_id: number | null;
    name: string;
    capacity: number;
    status: TableStatus;
    status_label: string;
    sort_order: number;
    is_active: boolean;
    zone?: Zone | null;
    created_at: string;
    updated_at: string;
}

export interface MenuCategory {
    uuid: string;
    name: string;
    description: string | null;
    station: Station;
    station_label: string;
    color: string | null;
    sort_order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface ProductImage {
    uuid: string;
    url: string;
    format: string | null;
    width: number | null;
    height: number | null;
    bytes: number | null;
    sort_order: number;
    created_at: string;
}

export interface Product {
    uuid: string;
    name: string;
    description: string | null;
    sku: string | null;
    price: string;
    cost: string | null;
    tax_type: TaxType;
    tax_type_label: string;
    station: Station;
    station_label: string;
    unit: string;
    is_available: boolean;
    track_stock: boolean;
    stock: string | null;
    image_path: string | null;
    sort_order: number;
    is_active: boolean;
    menu_category_id: number | null;
    category?: MenuCategory | null;
    images?: ProductImage[];
    modifier_groups?: ModifierGroup[];
    created_at: string;
    updated_at: string;
}

export interface Modifier {
    uuid: string;
    name: string;
    price: string;
    is_default: boolean;
    sort_order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface ModifierGroup {
    uuid: string;
    name: string;
    selection_type: ModifierSelectionType;
    selection_type_label: string;
    is_required: boolean;
    min_selections: number;
    max_selections: number;
    sort_order: number;
    is_active: boolean;
    modifiers?: Modifier[];
    created_at: string;
    updated_at: string;
}

export type OrderStatus = 'open' | 'sent' | 'served' | 'paid' | 'cancelled';
export type OrderType = 'dine_in' | 'takeaway' | 'delivery';
export type OrderItemStatus = 'pending' | 'preparing' | 'ready' | 'delivered' | 'void';

export interface OrderItemModifier {
    uuid: string;
    name: string;
    price: string;
    quantity: string;
}

export interface KitchenItem {
    uuid: string;
    order_uuid: string;
    order_number: number;
    order_type: OrderType;
    order_type_label: string;
    table_name: string | null;
    waiter_name: string | null;
    product_name: string;
    quantity: string;
    station: Station;
    station_label: string;
    status: OrderItemStatus;
    status_label: string;
    notes: string | null;
    modifiers: { name: string; quantity: string }[];
    sent_at: string | null;
    created_at: string;
    minutes_waiting: number;
}

export interface OrderItem {
    uuid: string;
    product_uuid: string | null;
    product_name: string;
    tax_type: TaxType;
    tax_type_label: string;
    station: Station;
    station_label: string;
    unit_price: string;
    modifiers_total: string;
    quantity: string;
    line_total: string;
    tax_amount: string;
    status: OrderItemStatus;
    status_label: string;
    notes: string | null;
    sort_order: number;
    modifiers: OrderItemModifier[];
}

export interface Order {
    uuid: string;
    number: number;
    type: OrderType;
    type_label: string;
    status: OrderStatus;
    status_label: string;
    guests: number;
    subtotal: string;
    tax_total: string;
    discount_total: string;
    tip_total: string;
    total: string;
    paid_total: string;
    remaining: number;
    notes: string | null;
    opened_at: string | null;
    paid_at: string | null;
    closed_at: string | null;
    dining_table: { uuid: string; name: string } | null;
    waiter: { uuid: string; name: string } | null;
    items_count?: number;
    items?: OrderItem[];
}

export type PaymentMethod = 'cash' | 'yape' | 'plin' | 'card' | 'transfer' | 'other';
export type CashSessionStatus = 'open' | 'closed';
export type CashMovementType = 'in' | 'out';

export interface CashSession {
    uuid: string;
    status: CashSessionStatus;
    status_label: string;
    opening_amount: string;
    expected_amount: string | null;
    closing_amount: string | null;
    difference: string | null;
    notes: string | null;
    opened_at: string | null;
    closed_at: string | null;
    opened_by: string | null;
    closed_by: string | null;
    payments_count?: number;
    movements_count?: number;
    movements?: CashMovement[];
}

export interface CashSummary {
    opening_amount: number;
    cash_total: number;
    payments_total: number;
    tips_total: number;
    movements_in: number;
    movements_out: number;
    expected_cash: number;
    orders_count: number;
}

export interface CashMovement {
    uuid: string;
    type: CashMovementType;
    type_label: string;
    amount: string;
    concept: string;
    notes: string | null;
    user: string | null;
    created_at: string;
}

export interface Payment {
    uuid: string;
    method: PaymentMethod;
    method_label: string;
    amount: string;
    tip: string;
    received_amount: string | null;
    change_amount: string | null;
    reference: string | null;
    notes: string | null;
    paid_at: string | null;
    order: { uuid: string; number: number } | null;
    user: string | null;
}

export type DocumentType = 'nota_venta' | 'boleta' | 'factura' | 'nota_credito';
export type DocumentStatus = 'draft' | 'issued' | 'accepted' | 'rejected' | 'annulled';
export type IdentityDocumentType = 'ruc' | 'dni' | 'ce' | 'passport' | 'other';
export type BillingMode = 'beta' | 'production';

export interface Customer {
    uuid: string;
    doc_type: IdentityDocumentType;
    doc_type_label: string;
    doc_number: string | null;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    notes: string | null;
    is_active: boolean;
    created_at: string;
}

export interface Document {
    uuid: string;
    type: DocumentType;
    type_label: string;
    type_is_electronic: boolean;
    series: string;
    number: number;
    full_number: string;
    status: DocumentStatus;
    status_label: string;
    customer_doc_type: IdentityDocumentType | null;
    customer_doc_type_label: string | null;
    customer_doc_number: string | null;
    customer_name: string | null;
    customer_address: string | null;
    customer_uuid?: string | null;
    currency: string;
    subtotal: string;
    tax_total: string;
    total: string;
    tip: string;
    issue_date: string | null;
    sunat_code: string | null;
    sunat_description: string | null;
    has_xml: boolean;
    has_cdr: boolean;
    has_pdf: boolean;
    sent_at: string | null;
    notes: string | null;
    created_at: string;
    order?: { uuid: string; number: number } | null;
}

export interface BillingSetting {
    enabled: boolean;
    ruc: string | null;
    business_name: string | null;
    trade_name: string | null;
    address: string | null;
    ubigeo: string | null;
    email: string | null;
    phone: string | null;
    sol_user: string | null;
    mode: BillingMode | null;
    mode_label: string | null;
    boleta_series: string | null;
    factura_series: string | null;
    legend: string | null;
    igv_rate: string;
    has_certificate: boolean;
    is_electronic_configured: boolean;
}

export interface PlatformSetting {
    cloudinary_enabled: boolean;
    cloudinary_cloud_name: string | null;
    cloudinary_api_key: string | null;
    cloudinary_folder: string | null;
    max_images_per_product: number;
    image_max_width: number;
    webp_quality: number;
    has_secret: boolean;
    is_configured: boolean;
}

export interface MenuCategorySummary {
    uuid: string;
    name: string;
    color: string | null;
    sort_order: number;
}

export interface MenuModifier {
    uuid: string;
    name: string;
    price: string;
    is_default: boolean;
}

export interface MenuModifierGroup {
    uuid: string;
    name: string;
    selection_type: ModifierSelectionType;
    is_required: boolean;
    min_selections: number;
    max_selections: number;
    modifiers: MenuModifier[];
}

export interface MenuProduct {
    uuid: string;
    name: string;
    description: string | null;
    price: string;
    is_available: boolean;
    tax_type: TaxType;
    station: Station;
    menu_category_uuid: string | null;
    modifier_groups: MenuModifierGroup[];
}

export interface MenuData {
    categories: MenuCategorySummary[];
    products: MenuProduct[];
}

export interface Role {
    id: number;
    name: string;
    label: string;
    guard_name: string;
    permissions: { name: string; label: string }[];
    users_count?: number;
}

export interface EnumOption<T extends string = string> {
    value: T;
    label: string;
}

export interface DashboardRange {
    from: string;
    to: string;
}

export interface DashboardSales {
    total: number;
    orders: number;
    average_ticket: number;
    tips: number;
}

export interface DashboardMethodSale {
    method: string;
    label: string;
    total: number;
    count: number;
}

export interface DashboardHourSale {
    hour: number;
    total: number;
    count: number;
}

export interface DashboardProductSale {
    name: string;
    quantity: number;
    total: number;
}

export interface DashboardTables {
    total: number;
    occupied: number;
    available: number;
    occupancy_rate: number;
}

export interface DashboardWaiterSale {
    name: string;
    orders: number;
    total: number;
}

export interface DashboardComparison {
    previous_total: number;
    change_percentage: number | null;
}

export interface DashboardReport {
    range: DashboardRange;
    sales: DashboardSales;
    sales_by_method: DashboardMethodSale[];
    sales_by_hour: DashboardHourSale[];
    top_products: DashboardProductSale[];
    sales_by_category: DashboardProductSale[];
    tables: DashboardTables;
    top_waiters: DashboardWaiterSale[];
    comparison: DashboardComparison;
    open_orders: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: { first: string | null; last: string | null; prev: string | null; next: string | null };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: PaginationLink[];
    };
}

export interface TenantSummary {
    uuid: string;
    name: string;
    slug: string;
}

export interface SharedData {
    auth: {
        user: User | null;
        roles: string[];
        permissions: string[];
    };
    tenant: Tenant | null;
    tenants: TenantSummary[];
    flash: {
        success?: string | null;
        error?: string | null;
    };
    app: {
        name: string;
        locale: string;
    };
    [key: string]: unknown;
}
