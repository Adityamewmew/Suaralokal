import db from '../db';
import { usersTable, umkmProfilesTable } from '../db/schema';
import { eq } from 'drizzle-orm';

// ponytail: demo UMKM profiles clustered around the discovery reference point (-8.10, 114.10)
// so the earthdistance radius search has deterministic nearest-first data to validate against.
const SEED_UMKM_PROFILES = [
    {
        userEmail: 'umkm@suaralokal.test',
        store_name: 'Toko Demo UMKM',
        description: 'Toko kelontong serba ada di pusat kota',
        address: 'Jl. Merdeka No. 1',
        category: 'kelontong',
        item_dimension: 'ringan',
        latitude: '-8.1050000',
        longitude: '114.1050000',
    },
    {
        userEmail: 'umkm2@suaralokal.test',
        store_name: 'Warung Menengah',
        description: 'Warung makan rumahan',
        address: 'Jl. Diponegoro No. 5',
        category: 'kuliner',
        item_dimension: 'ringan',
        latitude: '-8.1300000',
        longitude: '114.1300000',
    },
    {
        userEmail: 'umkm3@suaralokal.test',
        store_name: 'Depot Pinggir',
        description: 'Depot sembako pinggir kota',
        address: 'Jl. Ahmad Yani No. 20',
        category: 'sembako',
        item_dimension: 'sedang',
        latitude: '-8.2000000',
        longitude: '114.2000000',
    },
];

const SEED_USERS = [
    {
        name: 'Pengguna Demo',
        email: 'pengguna@suaralokal.test',
        role: 'pengguna',
        phone: '081234567890',
    },
    {
        name: 'Toko Demo UMKM',
        email: 'umkm@suaralokal.test',
        role: 'umkm',
        phone: '081234567891',
    },
    {
        name: 'Warung Menengah',
        email: 'umkm2@suaralokal.test',
        role: 'umkm',
        phone: '081234567894',
    },
    {
        name: 'Depot Pinggir',
        email: 'umkm3@suaralokal.test',
        role: 'umkm',
        phone: '081234567895',
    },
    {
        name: 'Admin Bangjek',
        email: 'ojekadmin@suaralokal.test',
        role: 'ojek_admin',
        phone: '081234567892',
    },
    {
        name: 'Driver Demo',
        email: 'driver@suaralokal.test',
        role: 'driver',
        phone: '081234567893',
    },
];

export async function suaraLokalSeeder(): Promise<void> {
    // bcrypt hash of 'password' with $2y$ prefix for Laravel compatibility
    const hash = await Bun.password.hash('password', {
        algorithm: 'bcrypt',
        cost: 12,
    });
    const hashedPassword = hash.replace(/^\$2b\$/, '$2y$');

    for (const userData of SEED_USERS) {
        const existing = await db
            .select({ id: usersTable.id })
            .from(usersTable)
            .where(eq(usersTable.email, userData.email))
            .limit(1);

        if (existing.length > 0) {
            await db
                .update(usersTable)
                .set({
                    name: userData.name,
                    role: userData.role,
                    phone: userData.phone,
                    password: hashedPassword,
                    updated_at: new Date(),
                })
                .where(eq(usersTable.email, userData.email));

            console.log(`[suaraLokalSeeder] Updated: ${userData.email} (${userData.role})`);
        } else {
            await db.insert(usersTable).values({
                name: userData.name,
                email: userData.email,
                role: userData.role,
                phone: userData.phone,
                password: hashedPassword,
                is_active: 1,
                created_at: new Date(),
                updated_at: new Date(),
            });

            console.log(`[suaraLokalSeeder] Created: ${userData.email} (${userData.role})`);
        }
    }

    // Upsert demo UMKM profiles (discovery needs coordinates to search).
    for (const profile of SEED_UMKM_PROFILES) {
        const [user] = await db
            .select({ id: usersTable.id })
            .from(usersTable)
            .where(eq(usersTable.email, profile.userEmail))
            .limit(1);

        if (!user) {
            continue;
        }

        const [existing] = await db
            .select({ id: umkmProfilesTable.id })
            .from(umkmProfilesTable)
            .where(eq(umkmProfilesTable.user_id, user.id))
            .limit(1);

        const values = {
            user_id: user.id,
            store_name: profile.store_name,
            description: profile.description,
            address: profile.address,
            phone: '081234567800',
            category: profile.category,
            item_dimension: profile.item_dimension,
            is_open: true,
            latitude: profile.latitude,
            longitude: profile.longitude,
            updated_at: new Date(),
        };

        if (existing) {
            await db
                .update(umkmProfilesTable)
                .set(values)
                .where(eq(umkmProfilesTable.user_id, user.id));
            console.log(`[suaraLokalSeeder] Updated profile: ${profile.store_name}`);
        } else {
            await db.insert(umkmProfilesTable).values({
                ...values,
                created_at: new Date(),
            });
            console.log(`[suaraLokalSeeder] Created profile: ${profile.store_name}`);
        }
    }
}
