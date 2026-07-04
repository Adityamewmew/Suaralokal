import db from '../db';
import { usersTable } from '../db/schema';
import { eq } from 'drizzle-orm';

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
}
