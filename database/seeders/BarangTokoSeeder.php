<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarangTokoSeeder extends Seeder
{
    public function run(): void
    {
        $barangToko = [
            // TKO001 - Toko Kue Sugeng
            ['BT001', 'TKO001', 'BRG0000005', 26000],
            ['BT002', 'TKO001', 'BRG0000001', 25000],
            ['BT003', 'TKO001', 'BRG0000004', 26000],
            ['BT004', 'TKO001', 'BRG0000003', 25000],
            ['BT005', 'TKO001', 'BRG0000002', 25000],
            // TKO002 - Risol Pastel Pasar Oro Oro Dowo
            ['BT036', 'TKO002', 'BRG0000005', 26000],
            ['BT037', 'TKO002', 'BRG0000001', 25000],
            ['BT038', 'TKO002', 'BRG0000004', 26000],
            ['BT039', 'TKO002', 'BRG0000003', 25000],
            ['BT040', 'TKO002', 'BRG0000002', 25000],
            // TKO003 - Warung Sayur Segar Kalpataru
            ['BT006', 'TKO003', 'BRG0000005', 26000],
            ['BT007', 'TKO003', 'BRG0000004', 26000],
            ['BT008', 'TKO003', 'BRG0000001', 25000],
            ['BT009', 'TKO003', 'BRG0000003', 25000],
            ['BT010', 'TKO003', 'BRG0000002', 25000],
            // TKO004 - Warung Sayur Segar Danau Sentani
            ['BT093', 'TKO004', 'BRG0000005', 26000],
            ['BT094', 'TKO004', 'BRG0000001', 25000],
            ['BT095', 'TKO004', 'BRG0000004', 26000],
            ['BT096', 'TKO004', 'BRG0000003', 25000],
            ['BT097', 'TKO004', 'BRG0000002', 25000],
            // TKO005 - Warung Sayur Segar Bandulan
            ['BT083', 'TKO005', 'BRG0000005', 26000],
            ['BT084', 'TKO005', 'BRG0000001', 25000],
            ['BT085', 'TKO005', 'BRG0000004', 26000],
            ['BT086', 'TKO005', 'BRG0000003', 25000],
            ['BT087', 'TKO005', 'BRG0000002', 25000],
            // TKO006 - Warung Sayur Segar Sulfat
            ['BT098', 'TKO006', 'BRG0000005', 26000],
            ['BT099', 'TKO006', 'BRG0000001', 25000],
            ['BT100', 'TKO006', 'BRG0000004', 26000],
            ['BT101', 'TKO006', 'BRG0000003', 25000],
            ['BT102', 'TKO006', 'BRG0000002', 25000],
            // TKO007 - Warung Sayur Segar Candi Panggung
            ['BT088', 'TKO007', 'BRG0000005', 26000],
            ['BT089', 'TKO007', 'BRG0000001', 25000],
            ['BT090', 'TKO007', 'BRG0000004', 26000],
            ['BT091', 'TKO007', 'BRG0000003', 25000],
            ['BT092', 'TKO007', 'BRG0000002', 25000],
            // TKO008 - Toko Firdaus
            ['BT041', 'TKO008', 'BRG0000005', 26000],
            ['BT042', 'TKO008', 'BRG0000001', 25000],
            ['BT043', 'TKO008', 'BRG0000004', 26000],
            ['BT044', 'TKO008', 'BRG0000003', 25000],
            ['BT045', 'TKO008', 'BRG0000002', 25000],
            // TKO009 - Toko Kue Selat Bali
            ['BT061', 'TKO009', 'BRG0000005', 26000],
            ['BT062', 'TKO009', 'BRG0000001', 25000],
            ['BT063', 'TKO009', 'BRG0000004', 26000],
            ['BT064', 'TKO009', 'BRG0000003', 25000],
            ['BT065', 'TKO009', 'BRG0000002', 25000],
            // TKO010 - Toko Kue Jaya Cookies
            ['BT051', 'TKO010', 'BRG0000005', 26000],
            ['BT052', 'TKO010', 'BRG0000001', 25000],
            ['BT053', 'TKO010', 'BRG0000004', 26000],
            ['BT054', 'TKO010', 'BRG0000003', 25000],
            ['BT055', 'TKO010', 'BRG0000002', 25000],
            // TKO011 - Istana Sayur
            ['BT016', 'TKO011', 'BRG0000005', 26000],
            ['BT017', 'TKO011', 'BRG0000001', 25000],
            ['BT018', 'TKO011', 'BRG0000004', 26000],
            ['BT019', 'TKO011', 'BRG0000003', 25000],
            ['BT020', 'TKO011', 'BRG0000002', 25000],
            // TKO012 - Pusat Mie Gloria
            ['BT031', 'TKO012', 'BRG0000005', 26000],
            ['BT032', 'TKO012', 'BRG0000001', 25000],
            ['BT033', 'TKO012', 'BRG0000004', 26000],
            ['BT034', 'TKO012', 'BRG0000003', 25000],
            ['BT035', 'TKO012', 'BRG0000002', 25000],
            // TKO013 - Koperasi UB
            ['BT026', 'TKO013', 'BRG0000005', 26000],
            ['BT027', 'TKO013', 'BRG0000001', 25000],
            ['BT028', 'TKO013', 'BRG0000004', 26000],
            ['BT029', 'TKO013', 'BRG0000003', 25000],
            ['BT030', 'TKO013', 'BRG0000002', 25000],
            // TKO014 - Warung Sayur
            ['BT078', 'TKO014', 'BRG0000005', 26000],
            ['BT079', 'TKO014', 'BRG0000001', 25000],
            ['BT080', 'TKO014', 'BRG0000004', 26000],
            ['BT081', 'TKO014', 'BRG0000003', 25000],
            ['BT082', 'TKO014', 'BRG0000002', 25000],
            // TKO015 - Toko Twins
            ['BT069', 'TKO015', 'BRG0000005', 26000],
            ['BT070', 'TKO015', 'BRG0000001', 25000],
            ['BT071', 'TKO015', 'BRG0000004', 26000],
            ['BT072', 'TKO015', 'BRG0000003', 25000],
            ['BT073', 'TKO015', 'BRG0000002', 25000],
            // TKO016 - Koperasi Dinkes
            ['BT021', 'TKO016', 'BRG0000005', 26000],
            ['BT022', 'TKO016', 'BRG0000001', 25000],
            ['BT023', 'TKO016', 'BRG0000004', 26000],
            ['BT024', 'TKO016', 'BRG0000003', 25000],
            ['BT025', 'TKO016', 'BRG0000002', 25000],
            // TKO017 - Warung Omah Sayur
            ['BT074', 'TKO017', 'BRG0000005', 26000],
            ['BT075', 'TKO017', 'BRG0000001', 25000],
            ['BT076', 'TKO017', 'BRG0000003', 25000],
            ['BT077', 'TKO017', 'BRG0000002', 25000],
            // TKO018 - Aneka Kue Basah Sengkaling
            ['BT011', 'TKO018', 'BRG0000005', 26000],
            ['BT012', 'TKO018', 'BRG0000001', 25000],
            ['BT013', 'TKO018', 'BRG0000004', 26000],
            ['BT014', 'TKO018', 'BRG0000003', 25000],
            ['BT015', 'TKO018', 'BRG0000002', 25000],
            // TKO019 - Toko Hijrah
            ['BT046', 'TKO019', 'BRG0000005', 26000],
            ['BT047', 'TKO019', 'BRG0000001', 25000],
            ['BT048', 'TKO019', 'BRG0000004', 26000],
            ['BT049', 'TKO019', 'BRG0000003', 25000],
            ['BT050', 'TKO019', 'BRG0000002', 25000],
            // TKO020 - Toko Kue Wendit
            ['BT066', 'TKO020', 'BRG0000001', 25000],
            ['BT067', 'TKO020', 'BRG0000004', 26000],
            ['BT068', 'TKO020', 'BRG0000002', 25000],
            // TKO021 - Toko Kue Puspa
            ['BT056', 'TKO021', 'BRG0000005', 26000],
            ['BT057', 'TKO021', 'BRG0000001', 25000],
            ['BT058', 'TKO021', 'BRG0000004', 26000],
            ['BT059', 'TKO021', 'BRG0000003', 25000],
            ['BT060', 'TKO021', 'BRG0000002', 25000],
        ];

        $data = [];
        foreach ($barangToko as $item) {
            $data[] = [
                'barang_toko_id' => $item[0],
                'toko_id' => $item[1],
                'barang_id' => $item[2],
                'harga_barang_toko' => $item[3],
                'user_create' => 'admin',
                'user_update' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('barang_toko')->insert($data);
    }
}
