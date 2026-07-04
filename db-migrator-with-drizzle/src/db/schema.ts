import {
    bigint,
    bigserial,
    boolean,
    index,
    integer,
    numeric,
    pgTable,
    serial,
    smallint,
    text,
    timestamp,
    uniqueIndex,
    varchar,
} from 'drizzle-orm/pg-core';
import type { AnyPgColumn } from 'drizzle-orm/pg-core';
import { sql } from 'drizzle-orm';

export const migrationsTable = pgTable('migrations', {
    id: serial('id').primaryKey(),
    migration: varchar('migration', { length: 255 }).notNull(),
    batch: integer('batch').notNull(),
});

export const usersTable = pgTable('users', {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    name: varchar('name', { length: 255 }).notNull(),
    email: varchar('email', { length: 255 }).notNull(),
    email_verified_at: timestamp('email_verified_at', { mode: 'date' }),
    password: varchar('password', { length: 255 }).notNull(),
    remember_token: varchar('remember_token', { length: 100 }),
    access_type: smallint('access_type'), // 1: Super Admin
    role: varchar('role', { length: 50 }).notNull().default('pengguna'),
    phone: varchar('phone', { length: 20 }),
    is_active: smallint('is_active').notNull().default(1),
    created_by: bigint('created_by', { mode: 'number' }).references((): AnyPgColumn => usersTable.id),
    created_at: timestamp('created_at', { mode: 'date' }),
    updated_by: bigint('updated_by', { mode: 'number' }).references((): AnyPgColumn => usersTable.id),
    updated_at: timestamp('updated_at', { mode: 'date' }),
    deleted_by: bigint('deleted_by', { mode: 'number' }).references((): AnyPgColumn => usersTable.id),
    deleted_at: timestamp('deleted_at', { mode: 'date' }),
}, (t) => [
    uniqueIndex('users_email_unique').on(t.email),
]);

export const passwordResetTokensTable = pgTable('password_reset_tokens', {
    email: varchar('email', { length: 255 }).notNull().primaryKey(),
    token: varchar('token', { length: 255 }).notNull(),
    created_at: timestamp('created_at', { mode: 'date' }),
});

export const sessionsTable = pgTable('sessions', {
    id: varchar('id', { length: 255 }).notNull().primaryKey(),
    user_id: bigint('user_id', { mode: 'number' }),
    ip_address: varchar('ip_address', { length: 45 }),
    user_agent: text('user_agent'),
    payload: text('payload').notNull(),
    last_activity: integer('last_activity').notNull(),
}, (t) => [
    index('sessions_user_id_index').on(t.user_id),
    index('sessions_last_activity_index').on(t.last_activity),
]);

export const cacheTable = pgTable('cache', {
    key: varchar('key', { length: 255 }).notNull().primaryKey(),
    value: text('value').notNull(),
    expiration: bigint('expiration', { mode: 'number' }).notNull(),
}, (t) => [
    index('cache_expiration_index').on(t.expiration),
]);

export const cacheLocksTable = pgTable('cache_locks', {
    key: varchar('key', { length: 255 }).notNull().primaryKey(),
    owner: varchar('owner', { length: 255 }).notNull(),
    expiration: bigint('expiration', { mode: 'number' }).notNull(),
}, (t) => [
    index('cache_locks_expiration_index').on(t.expiration),
]);

export const jobsTable = pgTable('jobs', {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    queue: varchar('queue', { length: 255 }).notNull(),
    payload: text('payload').notNull(),
    attempts: smallint('attempts').notNull(),
    reserved_at: integer('reserved_at'),
    available_at: integer('available_at').notNull(),
    created_at: integer('created_at').notNull(),
}, (t) => [
    index('jobs_queue_index').on(t.queue),
]);

export const jobBatchesTable = pgTable('job_batches', {
    id: varchar('id', { length: 255 }).notNull().primaryKey(),
    name: varchar('name', { length: 255 }).notNull(),
    total_jobs: integer('total_jobs').notNull(),
    pending_jobs: integer('pending_jobs').notNull(),
    failed_jobs: integer('failed_jobs').notNull(),
    failed_job_ids: text('failed_job_ids').notNull(),
    options: text('options'),
    cancelled_at: integer('cancelled_at'),
    created_at: integer('created_at').notNull(),
    finished_at: integer('finished_at'),
});

export const failedJobsTable = pgTable('failed_jobs', {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    uuid: varchar('uuid', { length: 255 }).notNull(),
    connection: varchar('connection', { length: 255 }).notNull(),
    queue: varchar('queue', { length: 255 }).notNull(),
    payload: text('payload').notNull(),
    exception: text('exception').notNull(),
    failed_at: timestamp('failed_at', { mode: 'date' }).notNull().defaultNow(),
}, (t) => [
    uniqueIndex('failed_jobs_uuid_unique').on(t.uuid),
    index('failed_jobs_connection_queue_failed_at_index').on(t.connection, t.queue, t.failed_at),
]);

export const sidebarMenuAccessesTable = pgTable('sidebar_menu_accesses', {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    sidebar_menu_id: bigint('sidebar_menu_id', { mode: 'number' }).notNull(),
    access_type: smallint('access_type').notNull(),
    created_by: bigint('created_by', { mode: 'number' }),
    created_at: timestamp('created_at', { mode: 'date' }),
}, (t) => [
    uniqueIndex('sidebar_menu_accesses_sidebar_menu_id_access_type_unique').on(t.sidebar_menu_id, t.access_type),
    index('sidebar_menu_accesses_sidebar_menu_id_index').on(t.sidebar_menu_id),
]);

export const sidebarMenuGroupsTable = pgTable('sidebar_menu_groups', {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    key: varchar('key', { length: 50 }).notNull(),
    label: varchar('label', { length: 100 }).notNull(),
    color: varchar('color', { length: 50 }).notNull().default('blue'),
    sort_order: smallint('sort_order').notNull().default(0),
    created_at: timestamp('created_at', { mode: 'date' }),
    updated_at: timestamp('updated_at', { mode: 'date' }),
}, (t) => [
    uniqueIndex('sidebar_menu_groups_key_unique').on(t.key),
]);

export const sidebarMenusTable = pgTable('sidebar_menus', {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    parent_id: bigint('parent_id', { mode: 'number' }),
    label: varchar('label', { length: 255 }).notNull(),
    route_name: varchar('route_name', { length: 255 }),
    icon: varchar('icon', { length: 255 }),
    group: varchar('group', { length: 50 }).notNull(),
    sort_order: smallint('sort_order').notNull().default(0),
    is_active: smallint('is_active').notNull().default(1),
    created_by: bigint('created_by', { mode: 'number' }),
    updated_by: bigint('updated_by', { mode: 'number' }),
    deleted_by: bigint('deleted_by', { mode: 'number' }),
    created_at: timestamp('created_at', { mode: 'date' }),
    updated_at: timestamp('updated_at', { mode: 'date' }),
    deleted_at: timestamp('deleted_at', { mode: 'date' }),
}, (t) => [
    index('sidebar_menus_parent_id_index').on(t.parent_id),
    index('sidebar_menus_group_sort_order_index').on(t.group, t.sort_order),
]);

// ─── SuaraLokal MVP Tables ────────────────────────────────────────────

export const umkmProfilesTable = pgTable('umkm_profiles', {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    user_id: bigint('user_id', { mode: 'number' }).notNull().references(() => usersTable.id),
    store_name: varchar('store_name', { length: 255 }).notNull(),
    description: text('description'),
    address: text('address'),
    phone: varchar('phone', { length: 20 }),
    category: varchar('category', { length: 50 }),
    item_dimension: varchar('item_dimension', { length: 20 }), // ringan, sedang, besar
    is_open: boolean('is_open').notNull().default(false),
    latitude: numeric('latitude', { precision: 10, scale: 7 }),
    longitude: numeric('longitude', { precision: 10, scale: 7 }),
    created_at: timestamp('created_at', { mode: 'date' }),
    updated_at: timestamp('updated_at', { mode: 'date' }),
}, (t) => [
    uniqueIndex('umkm_profiles_user_id_unique').on(t.user_id),
    // ponytail: GiST expression index on ll_to_earth(lat,lng) backs earthdistance radius search
    // (cube + earthdistance extensions) in place of a full PostGIS geometry column.
    index('umkm_profiles_ll_to_earth_gist_idx').using('gist', sql`ll_to_earth(latitude, longitude)`),
]);

export const conversationsTable = pgTable('conversations', {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    pengguna_id: bigint('pengguna_id', { mode: 'number' }).notNull().references(() => usersTable.id),
    umkm_id: bigint('umkm_id', { mode: 'number' }).notNull().references(() => usersTable.id),
    created_at: timestamp('created_at', { mode: 'date' }),
    updated_at: timestamp('updated_at', { mode: 'date' }),
}, (t) => [
    uniqueIndex('conversations_pengguna_umkm_unique').on(t.pengguna_id, t.umkm_id),
    index('conversations_pengguna_id_index').on(t.pengguna_id),
    index('conversations_umkm_id_index').on(t.umkm_id),
]);

export const messagesTable = pgTable('messages', {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    conversation_id: bigint('conversation_id', { mode: 'number' }).notNull().references(() => conversationsTable.id),
    sender_id: bigint('sender_id', { mode: 'number' }).notNull().references(() => usersTable.id),
    content: text('content').notNull(),
    created_at: timestamp('created_at', { mode: 'date' }),
}, (t) => [
    index('messages_conversation_id_index').on(t.conversation_id),
    index('messages_sender_id_index').on(t.sender_id),
]);

export const ordersTable = pgTable('orders', {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    pengguna_id: bigint('pengguna_id', { mode: 'number' }).notNull().references(() => usersTable.id),
    umkm_id: bigint('umkm_id', { mode: 'number' }).notNull().references(() => usersTable.id),
    driver_id: bigint('driver_id', { mode: 'number' }).references(() => usersTable.id),
    conversation_id: bigint('conversation_id', { mode: 'number' }).references(() => conversationsTable.id),
    total_items_price: numeric('total_items_price', { precision: 12, scale: 2 }).notNull().default('0'),
    shipping_fee: numeric('shipping_fee', { precision: 12, scale: 2 }).notNull().default('0'),
    payment_method: varchar('payment_method', { length: 20 }).notNull(), // cod_talangan, non_tunai
    order_status: varchar('order_status', { length: 30 }).notNull().default('tunggu_konfirm'),
    proof_of_delivery_url: varchar('proof_of_delivery_url', { length: 500 }),
    created_at: timestamp('created_at', { mode: 'date' }),
    updated_at: timestamp('updated_at', { mode: 'date' }),
}, (t) => [
    index('orders_pengguna_id_index').on(t.pengguna_id),
    index('orders_umkm_id_index').on(t.umkm_id),
    index('orders_driver_id_index').on(t.driver_id),
    index('orders_order_status_index').on(t.order_status),
]);

export const orderItemsTable = pgTable('order_items', {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    order_id: bigint('order_id', { mode: 'number' }).notNull().references(() => ordersTable.id),
    item_name: varchar('item_name', { length: 255 }).notNull(),
    quantity: integer('quantity').notNull().default(1),
    price: numeric('price', { precision: 12, scale: 2 }).notNull().default('0'),
    created_at: timestamp('created_at', { mode: 'date' }),
    updated_at: timestamp('updated_at', { mode: 'date' }),
}, (t) => [
    index('order_items_order_id_index').on(t.order_id),
]);

export const settlementsTable = pgTable('settlements', {
    id: bigserial('id', { mode: 'number' }).primaryKey(),
    order_id: bigint('order_id', { mode: 'number' }).notNull().references(() => ordersTable.id),
    amount: numeric('amount', { precision: 12, scale: 2 }).notNull().default('0'),
    status: varchar('status', { length: 30 }).notNull().default('menunggu_reimburse'), // menunggu_reimburse, selesai_reimburse
    settled_at: timestamp('settled_at', { mode: 'date' }),
    created_at: timestamp('created_at', { mode: 'date' }),
    updated_at: timestamp('updated_at', { mode: 'date' }),
}, (t) => [
    index('settlements_order_id_index').on(t.order_id),
    index('settlements_status_index').on(t.status),
]);
