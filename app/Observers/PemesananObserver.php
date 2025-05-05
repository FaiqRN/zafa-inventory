<?php

namespace App\Observers;

use App\Models\Pemesanan;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class PemesananObserver
{
    /**
     * Handle the Pemesanan "created" event.
     */
    public function created(Pemesanan $pemesanan): void
    {
        Log::info('PemesananObserver: Processing new pemesanan: ' . $pemesanan->pemesanan_id);
        
        // Cek apakah ada data customer untuk diambil
        if (!empty($pemesanan->nama_pemesan)) {
            // Cek apakah customer dengan email yang sama sudah ada
            $existingCustomer = null;
            if (!empty($pemesanan->email_pemesan)) {
                $existingCustomer = Customer::where('email', $pemesanan->email_pemesan)->first();
            }
            
            if (!$existingCustomer && !empty($pemesanan->no_telp_pemesan)) {
                $existingCustomer = Customer::where('no_tlp', $pemesanan->no_telp_pemesan)->first();
            }
            
            if ($existingCustomer) {
                // Update customer yang sudah ada
                Log::info('PemesananObserver: Updating existing customer: ' . $existingCustomer->customer_id);
                
                $existingCustomer->update([
                    'nama' => $pemesanan->nama_pemesan,
                    'alamat' => $pemesanan->alamat_pemesan,
                    'email' => $pemesanan->email_pemesan,
                    'no_tlp' => $pemesanan->no_telp_pemesan,
                    'pemesanan_id' => $pemesanan->pemesanan_id
                ]);
            } else {
                // Buat customer baru
                Log::info('PemesananObserver: Creating new customer from pemesanan');
                
                Customer::create([
                    'nama' => $pemesanan->nama_pemesan,
                    'alamat' => $pemesanan->alamat_pemesan,
                    'email' => $pemesanan->email_pemesan,
                    'no_tlp' => $pemesanan->no_telp_pemesan,
                    'pemesanan_id' => $pemesanan->pemesanan_id
                ]);
            }
        }
    }

    /**
     * Handle the Pemesanan "updated" event.
     */
    public function updated(Pemesanan $pemesanan): void
    {
        // Update customer data jika ada perubahan pada data pemesanan
        if (!empty($pemesanan->pemesanan_id)) {
            $customer = Customer::where('pemesanan_id', $pemesanan->pemesanan_id)->first();
            
            if ($customer) {
                Log::info('PemesananObserver: Updating customer from updated pemesanan: ' . $customer->customer_id);
                
                $customer->update([
                    'nama' => $pemesanan->nama_pemesan,
                    'alamat' => $pemesanan->alamat_pemesan,
                    'email' => $pemesanan->email_pemesan,
                    'no_tlp' => $pemesanan->no_telp_pemesan
                ]);
            }
        }
    }
}