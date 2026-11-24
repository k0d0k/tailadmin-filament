<?php

namespace App\Support;

/**
 * Canonical role taxonomy of the SaaS boilerplate.
 *
 * The central database is operated by the Super Admin and Operator, while a
 * tenant database ships with the tenant level roles below.
 */
class Roles
{
    /* Central roles */
    public const SUPER_ADMIN = 'super_admin';
    public const OPERATOR = 'operator';

    /* Tenant roles */
    public const TENANT_ADMIN = 'tenant_admin';
    public const MANAJEMEN = 'manajemen';
    public const KEPALA_SEKOLAH = 'kepala_sekolah';
    public const GURU = 'guru';
    public const PETUGAS_PIKET = 'petugas_piket';
    public const ASRAMA = 'asrama';
    public const KEAMANAN = 'keamanan';
    public const ORANG_TUA = 'orang_tua';
    public const SISWA = 'siswa';

    /**
     * Roles that live in the central database.
     *
     * @return array<int, string>
     */
    public static function central(): array
    {
        return [self::SUPER_ADMIN, self::OPERATOR];
    }

    /**
     * Roles provisioned for every tenant.
     *
     * @return array<int, string>
     */
    public static function tenant(): array
    {
        return [
            self::TENANT_ADMIN,
            self::MANAJEMEN,
            self::KEPALA_SEKOLAH,
            self::GURU,
            self::PETUGAS_PIKET,
            self::ASRAMA,
            self::KEAMANAN,
            self::ORANG_TUA,
            self::SISWA,
        ];
    }

    /**
     * Human readable labels keyed by role name.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::SUPER_ADMIN => 'Super Admin',
            self::OPERATOR => 'Operator',
            self::TENANT_ADMIN => 'Tenant Admin',
            self::MANAJEMEN => 'Manajemen',
            self::KEPALA_SEKOLAH => 'Kepala Sekolah',
            self::GURU => 'Guru',
            self::PETUGAS_PIKET => 'Petugas Piket',
            self::ASRAMA => 'Asrama',
            self::KEAMANAN => 'Keamanan',
            self::ORANG_TUA => 'Orang Tua',
            self::SISWA => 'Siswa',
        ];
    }
}
