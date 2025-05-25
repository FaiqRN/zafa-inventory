<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MarketMapController extends Controller
{
    public function index()
    {
        return view('market.map', [
            'activemenu' => 'market-map',
            'breadcrumb' => (object) [
                'title' => 'Market Map',
                'list' => ['Home', 'Market Map']
            ]
        ]);
    }

    public function getTokoData()
    {
        try {
            $tokoData = DB::table('toko')
                ->select([
                    'toko_id',
                    'nama_toko',
                    'pemilik',
                    'alamat',
                    'wilayah_kelurahan',
                    'wilayah_kecamatan',
                    'wilayah_kota_kabupaten',
                    'nomer_telpon'
                ])
                ->get();

            // Transform data untuk peta
            $mapData = $tokoData->map(function ($toko) {
                // Generate koordinat dummy berdasarkan wilayah
                $coordinates = $this->generateCoordinatesByWilayah(
                    $toko->wilayah_kota_kabupaten,
                    $toko->wilayah_kecamatan,
                    $toko->wilayah_kelurahan
                );

                // Hitung jumlah barang di toko
                $jumlahBarang = DB::table('barang_toko')
                    ->where('toko_id', $toko->toko_id)
                    ->count();

                // Hitung total pengiriman
                $totalPengiriman = DB::table('pengiriman')
                    ->where('toko_id', $toko->toko_id)
                    ->count();

                // Hitung total retur
                $totalRetur = DB::table('retur')
                    ->where('toko_id', $toko->toko_id)
                    ->count();

                return [
                    'toko_id' => $toko->toko_id,
                    'nama_toko' => $toko->nama_toko,
                    'pemilik' => $toko->pemilik,
                    'alamat' => $toko->alamat,
                    'kelurahan' => $toko->wilayah_kelurahan,
                    'kecamatan' => $toko->wilayah_kecamatan,
                    'kota_kabupaten' => $toko->wilayah_kota_kabupaten,
                    'telpon' => $toko->nomer_telpon,
                    'latitude' => $coordinates['lat'],
                    'longitude' => $coordinates['lng'],
                    'jumlah_barang' => $jumlahBarang,
                    'total_pengiriman' => $totalPengiriman,
                    'total_retur' => $totalRetur,
                    'status_aktif' => $jumlahBarang > 0 ? 'Aktif' : 'Tidak Aktif'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $mapData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getWilayahStatistics()
    {
        try {
            // Statistik per kecamatan
            $statistikKecamatan = DB::table('toko')
                ->select([
                    'wilayah_kecamatan',
                    'wilayah_kota_kabupaten',
                    DB::raw('COUNT(*) as jumlah_toko')
                ])
                ->groupBy('wilayah_kecamatan', 'wilayah_kota_kabupaten')
                ->get();

            // Statistik per kelurahan
            $statistikKelurahan = DB::table('toko')
                ->select([
                    'wilayah_kelurahan',
                    'wilayah_kecamatan',
                    'wilayah_kota_kabupaten',
                    DB::raw('COUNT(*) as jumlah_toko')
                ])
                ->groupBy('wilayah_kelurahan', 'wilayah_kecamatan', 'wilayah_kota_kabupaten')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'kecamatan' => $statistikKecamatan,
                    'kelurahan' => $statistikKelurahan
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTokoBarang($tokoId)
    {
        try {
            $barangToko = DB::table('barang_toko')
                ->join('barang', 'barang_toko.barang_id', '=', 'barang.barang_id')
                ->join('toko', 'barang_toko.toko_id', '=', 'toko.toko_id')
                ->where('barang_toko.toko_id', $tokoId)
                ->select([
                    'barang.barang_id',
                    'barang.nama_barang',
                    'barang.barang_kode',
                    'barang.harga_awal_barang',
                    'barang_toko.harga_barang_toko',
                    'barang.satuan',
                    'barang.keterangan',
                    'toko.nama_toko'
                ])
                ->get();

            // Statistik pengiriman untuk toko ini
            $statistikPengiriman = DB::table('pengiriman')
                ->where('toko_id', $tokoId)
                ->select([
                    DB::raw('COUNT(*) as total_pengiriman'),
                    DB::raw('SUM(jumlah_kirim) as total_barang_dikirim'),
                    DB::raw('MAX(tanggal_pengiriman) as pengiriman_terakhir')
                ])
                ->first();

            // Statistik retur untuk toko ini
            $statistikRetur = DB::table('retur')
                ->where('toko_id', $tokoId)
                ->select([
                    DB::raw('COUNT(*) as total_retur'),
                    DB::raw('SUM(jumlah_retur) as total_barang_retur'),
                    DB::raw('SUM(hasil) as total_hasil_retur')
                ])
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'barang' => $barangToko,
                    'statistik_pengiriman' => $statistikPengiriman,
                    'statistik_retur' => $statistikRetur
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getWilayahData()
    {
        try {
            // Baca file wilayah_malang.json
            $wilayahPath = resource_path('data/wilayah_malang.json');
            
            if (!file_exists($wilayahPath)) {
                // Fallback: coba dari storage atau public
                $wilayahPath = public_path('data/wilayah_malang.json');
            }

            if (file_exists($wilayahPath)) {
                $wilayahData = json_decode(file_get_contents($wilayahPath), true);
            } else {
                // Fallback data minimal
                $wilayahData = [
                    'wilayah' => [
                        [
                            'id' => 'kotamalang',
                            'nama' => 'Kota Malang',
                            'kecamatan' => []
                        ],
                        [
                            'id' => 'kabmalang',
                            'nama' => 'Kabupaten Malang',
                            'kecamatan' => []
                        ],
                        [
                            'id' => 'kotabatu',
                            'nama' => 'Kota Batu',
                            'kecamatan' => []
                        ]
                    ]
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $wilayahData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRecommendations()
    {
        try {
            // Analisis wilayah dengan potensi tinggi tapi toko sedikit
            $potensialWilayah = DB::table('toko')
                ->select([
                    'wilayah_kecamatan',
                    'wilayah_kota_kabupaten',
                    DB::raw('COUNT(*) as jumlah_toko')
                ])
                ->groupBy('wilayah_kecamatan', 'wilayah_kota_kabupaten')
                ->having('jumlah_toko', '<', 3) // Wilayah dengan toko sedikit
                ->orderBy('jumlah_toko', 'asc')
                ->limit(10)
                ->get();

            // Barang yang paling banyak dipesan
            $barangPopuler = DB::table('pengiriman')
                ->join('barang', 'pengiriman.barang_id', '=', 'barang.barang_id')
                ->select([
                    'barang.nama_barang',
                    'barang.barang_kode',
                    DB::raw('SUM(pengiriman.jumlah_kirim) as total_dikirim'),
                    DB::raw('COUNT(DISTINCT pengiriman.toko_id) as jumlah_toko')
                ])
                ->groupBy('barang.barang_id', 'barang.nama_barang', 'barang.barang_kode')
                ->orderBy('total_dikirim', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'potensial_wilayah' => $potensialWilayah,
                    'barang_populer' => $barangPopuler
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPriceRecommendations()
    {
        try {
            // Analisis harga per wilayah
            $analisisHarga = DB::table('barang_toko')
                ->join('barang', 'barang_toko.barang_id', '=', 'barang.barang_id')
                ->join('toko', 'barang_toko.toko_id', '=', 'toko.toko_id')
                ->select([
                    'barang.nama_barang',
                    'toko.wilayah_kecamatan',
                    'toko.wilayah_kota_kabupaten',
                    DB::raw('AVG(barang_toko.harga_barang_toko) as rata_harga'),
                    DB::raw('MIN(barang_toko.harga_barang_toko) as harga_terendah'),
                    DB::raw('MAX(barang_toko.harga_barang_toko) as harga_tertinggi'),
                    DB::raw('COUNT(*) as jumlah_toko')
                ])
                ->groupBy('barang.barang_id', 'barang.nama_barang', 'toko.wilayah_kecamatan', 'toko.wilayah_kota_kabupaten')
                ->having('jumlah_toko', '>=', 2)
                ->orderBy('rata_harga', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $analisisHarga
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateCoordinatesByWilayah($kotaKabupaten, $kecamatan, $kelurahan)
    {
        // Base coordinates untuk wilayah Malang
        $baseCoordinates = [
            'Kota Malang' => ['lat' => -7.9666, 'lng' => 112.6326],
            'Kabupaten Malang' => ['lat' => -8.1844, 'lng' => 112.7456],
            'Kota Batu' => ['lat' => -7.8767, 'lng' => 112.5326]
        ];

        // Ambil base coordinate
        $base = $baseCoordinates[$kotaKabupaten] ?? ['lat' => -7.9666, 'lng' => 112.6326];

        // Generate offset berdasarkan hash nama kecamatan dan kelurahan
        $hashKecamatan = crc32($kecamatan);
        $hashKelurahan = crc32($kelurahan);
        
        // Offset dalam radius 0.05 derajat (sekitar 5km)
        $offsetLat = (($hashKecamatan % 1000) / 10000) - 0.05;
        $offsetLng = (($hashKelurahan % 1000) / 10000) - 0.05;

        return [
            'lat' => $base['lat'] + $offsetLat,
            'lng' => $base['lng'] + $offsetLng
        ];
    }

    public function storeToko(Request $request)
    {
        try {
            $request->validate([
                'nama_toko' => 'required|string|max:100',
                'pemilik' => 'required|string|max:100',
                'alamat' => 'required|string',
                'kota_kabupaten' => 'required|string',
                'kecamatan' => 'required|string',
                'kelurahan' => 'required|string',
                'nomer_telpon' => 'required|string|max:20'
            ]);

            // Generate ID toko
            $lastToko = DB::table('toko')->orderBy('toko_id', 'desc')->first();
            $newId = $lastToko ? 'TK' . str_pad((intval(substr($lastToko->toko_id, 2)) + 1), 3, '0', STR_PAD_LEFT) : 'TK001';

            DB::table('toko')->insert([
                'toko_id' => $newId,
                'nama_toko' => $request->nama_toko,
                'pemilik' => $request->pemilik,
                'alamat' => $request->alamat,
                'wilayah_kota_kabupaten' => $request->kota_kabupaten,
                'wilayah_kecamatan' => $request->kecamatan,
                'wilayah_kelurahan' => $request->kelurahan,
                'nomer_telpon' => $request->nomer_telpon
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Toko berhasil ditambahkan',
                'data' => ['toko_id' => $newId]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}