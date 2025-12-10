<?php

use App\Services\CurrencyConverter;

if (!function_exists('convertCurrency')) {
    /**
     * Convert amount between currencies
     * 
     * @param float $amount
     * @param string $from
     * @param string $to
     * @return float
     */
    function convertCurrency($amount, $from = 'USD', $to = 'IDR')
    {
        $converter = app(CurrencyConverter::class);
        
        if ($from === 'USD' && $to === 'IDR') {
            return $converter->convertUsdToIdr($amount);
        }
        
        if ($from === 'IDR' && $to === 'USD') {
            return $converter->convertIdrToUsd($amount);
        }
        
        return $amount;
    }
}

if (!function_exists('formatCurrency')) {
    /**
     * Format currency with proper formatting
     * 
     * @param float $amount
     * @param string $currency
     * @return string
     */
    function formatCurrency($amount, $currency = 'USD')
    {
        $converter = app(CurrencyConverter::class);
        return $converter->formatCurrency($amount, $currency);
    }
}

if (!function_exists('getExchangeRate')) {
    /**
     * Get current exchange rate
     * 
     * @return array
     */
    function getExchangeRate()
    {
        $converter = app(CurrencyConverter::class);
        return $converter->getRateInfo();
    }
}