<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyConverter
{
    private $apiKey;
    private $baseUrl = 'https://api.exchangerate-api.com/v4/latest/USD';

    public function __construct()
    {
        $this->apiKey = env('CURRENCY_API_KEY', null);
    }

    /**
     * Get current USD to IDR exchange rate
     * 
     * @return float
     */
    public function getUsdToIdrRate()
    {
        // Try to get from cache first
        return Cache::remember('usd_to_idr_rate', 3600, function () { // Cache for 1 hour
            return $this->fetchExchangeRate();
        });
    }

    /**
     * Fetch exchange rate from API
     * 
     * @return float
     */
    private function fetchExchangeRate()
    {
        try {
            // Try multiple free APIs
            $apis = [
                'https://api.exchangerate-api.com/v4/latest/USD',
                'https://api.exchangerate.host/latest?base=USD',
                'https://open.er-api.com/v6/latest/USD'
            ];

            foreach ($apis as $apiUrl) {
                try {
                    $response = Http::timeout(5)->get($apiUrl);
                    
                    if ($response->successful()) {
                        $data = $response->json();
                        
                        // Check different API response structures
                        if (isset($data['rates']['IDR'])) {
                            return $data['rates']['IDR'];
                        } elseif (isset($data['conversion_rates']['IDR'])) {
                            return $data['conversion_rates']['IDR'];
                        }
                    }
                } catch (\Exception $e) {
                    // Try next API
                    continue;
                }
            }

            // If all APIs fail, return default rate
            return $this->getDefaultRate();
        } catch (\Exception $e) {
            return $this->getDefaultRate();
        }
    }

    /**
     * Get default exchange rate (fallback)
     * 
     * @return float
     */
    private function getDefaultRate()
    {
        // Return a reasonable default (average recent rate)
        return 16000.00;
    }

    /**
     * Convert amount from USD to IDR
     * 
     * @param float $amount
     * @return float
     */
    public function convertUsdToIdr($amount)
    {
        $rate = $this->getUsdToIdrRate();
        return $amount * $rate;
    }

    /**
     * Convert amount from IDR to USD
     * 
     * @param float $amount
     * @return float
     */
    public function convertIdrToUsd($amount)
    {
        $rate = $this->getUsdToIdrRate();
        return $amount / $rate;
    }

    /**
     * Format currency with proper formatting
     * 
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public function formatCurrency($amount, $currency = 'USD')
    {
        if ($currency === 'IDR') {
            return 'Rp ' . number_format($amount, 0, ',', '.');
        }
        
        return '$ ' . number_format($amount, 2, '.', ',');
    }

    /**
     * Get the current exchange rate info
     * 
     * @return array
     */
    public function getRateInfo()
    {
        $rate = $this->getUsdToIdrRate();
        $updatedAt = Cache::get('usd_to_idr_rate_updated_at', now()->subHours(2));
        
        return [
            'rate' => $rate,
            'formatted_rate' => number_format($rate, 2, '.', ','),
            'updated_at' => $updatedAt,
            'is_live' => !$this->isUsingDefaultRate(),
        ];
    }

    /**
     * Check if using default rate
     * 
     * @return bool
     */
    private function isUsingDefaultRate()
    {
        return Cache::get('using_default_rate', false);
    }
}