<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\ColorSize;
use App\Models\StockImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StockWebsiteSyncService
{
    /**
     * API endpoint for website synchronization
     */
    private const API_URL = 'https://duo-fashion.com/admin_advance/Api/add_stock_website';

    /**
     * Delivery status constants
     */
    const STATUS_PENDING = 1;  // Pending / Sent
    const STATUS_CONFIRMED = 2; // Website confirmed
    const STATUS_FAILED = 0;    // Failed

    /**
     * Sync stock data to external website
     * Only sends data from color_sizes table (where both color_id AND size_id exist)
     * 
     * @param int $stockId
     * @return bool
     */
    public function syncStockToWebsite(int $stockId): bool
    {
        try {
            // Fetch latest stock data from database (not from request)
            $stock = Stock::with([
                'colorSizes' => function ($query) {
                    // Only get records where both color_id AND size_id exist
                    $query->whereNotNull('color_id')
                          ->whereNotNull('size_id')
                          ->where('color_id', '!=', '')
                          ->where('size_id', '!=', '');
                },
                'images'
            ])->find($stockId);

            if (!$stock) {
                Log::warning("StockWebsiteSyncService: Stock ID {$stockId} not found");
                return false;
            }

            // Check if there are any valid color_sizes records
            $validColorSizes = $stock->colorSizes->filter(function ($colorSize) {
                return !empty($colorSize->color_id) && !empty($colorSize->size_id);
            });

            if ($validColorSizes->isEmpty()) {
                Log::info("StockWebsiteSyncService: Stock ID {$stockId} has no valid color_sizes records (both color_id and size_id required)");
                // Still update status to indicate we tried
                $this->updateDeliveryStatus($stockId, self::STATUS_PENDING);
                return false;
            }

            // Build payload
            $payload = $this->buildPayload($stock, $validColorSizes);

            // Set status to pending before sending
            $this->updateDeliveryStatus($stockId, self::STATUS_PENDING);

            // Send to API
            $response = Http::timeout(30)->post(self::API_URL, $payload); 

           
            
            
            // Check if request was successful
            if ($response->successful()) {
                // Try to parse response to check if website confirmed
                $responseData = $response->json();
                 // DEBUG RESPONSE
                Log::info('Website API response', [
                    'status' => $responseData['body'],
                    'body'   => $response->json()
                ]);
                // Update status based on response
                // If API returns success/confirmed, set to 2, otherwise keep as 1
                if (isset($responseData['status']) && ($responseData['status'] === 'success' || $responseData['status'] === true)) {
                    $this->updateDeliveryStatus($stockId, self::STATUS_CONFIRMED);
                    Log::info("StockWebsiteSyncService: Stock ID {$stockId} synced successfully to website");
                    return true;
                } else {
                    // API responded but didn't confirm success
                    $this->updateDeliveryStatus($stockId, self::STATUS_PENDING);
                    Log::warning("StockWebsiteSyncService: Stock ID {$stockId} API responded but status not confirmed", [
                        'response' => $responseData
                    ]);
                    return false;
                }
            } else {
                // API request failed
                $this->updateDeliveryStatus($stockId, self::STATUS_FAILED);
                Log::error("StockWebsiteSyncService: Failed to sync Stock ID {$stockId} to website", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            // Update status to failed on exception
            $this->updateDeliveryStatus($stockId, self::STATUS_FAILED);
            Log::error("StockWebsiteSyncService: Exception while syncing Stock ID {$stockId}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Build payload for API request
     * 
     * @param Stock $stock
     * @param \Illuminate\Support\Collection $colorSizes
     * @return array
     */
    private function buildPayload(Stock $stock, $colorSizes): array
    {
        // Build stock main data
        $stockData = [
            'id' => $stock->id,
            'abaya_code' => $stock->abaya_code ?? '',
            'barcode' => $stock->barcode ?? '',
            'design_name' => $stock->design_name ?? '',
            'sales_price' => $stock->sales_price ?? 0,
            'quantity' => $stock->quantity ?? 0,
        ];

        // Build color_sizes array (only from color_sizes table)
        $colorSizesArray = [];
        foreach ($colorSizes as $colorSize) {
            // Ensure both color_id and size_id exist
            if (!empty($colorSize->color_id) && !empty($colorSize->size_id)) {
                $colorSizesArray[] = [
                    'color_id' => (int)$colorSize->color_id,
                    'size_id' => (int)$colorSize->size_id,
                    'qty' => (int)($colorSize->qty ?? 0),
                ];
            }
        }

        // Build images array
        $imagesArray = [];
        foreach ($stock->images as $image) {
            if (!empty($image->image_path)) {
                $imagesArray[] = $image->image_path;
            }
        }

        return [
            'stock' => $stockData,
            'color_sizes' => $colorSizesArray,
            'images' => $imagesArray,
        ];
    }

    /**
     * Update delivery status in database
     * 
     * @param int $stockId
     * @param int $status
     * @return void
     */
    private function updateDeliveryStatus(int $stockId, int $status): void
    {
        try {
            Stock::where('id', $stockId)->update([
                'website_data_delivery_status' => $status
            ]);
        } catch (\Exception $e) {
            Log::error("StockWebsiteSyncService: Failed to update delivery status for Stock ID {$stockId}", [
                'status' => $status,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sync stock asynchronously (dispatch to queue if available, otherwise run in background)
     * This ensures stock operations are not blocked
     * 
     * @param int $stockId
     * @return void
     */
    public function syncStockAsync(int $stockId): void
    {
        // Run in background without blocking
        // Use Laravel's dispatch helper if available, otherwise run after response
        try {
            // Try to dispatch to queue if available
            if (function_exists('dispatch')) {
                dispatch(function () use ($stockId) {
                    $this->syncStockToWebsite($stockId);
                })->afterResponse();
            } else {
                // Fallback: Use register_shutdown_function to run after response
                register_shutdown_function(function () use ($stockId) {
                    try {
                        $this->syncStockToWebsite($stockId);
                    } catch (\Exception $e) {
                        Log::error("StockWebsiteSyncService: Error in shutdown function sync for Stock ID {$stockId}", [
                            'error' => $e->getMessage()
                        ]);
                    }
                });
            }
        } catch (\Exception $e) {
            // If dispatch fails, try sync directly but log it
            Log::warning("StockWebsiteSyncService: Failed to dispatch async sync for Stock ID {$stockId}, running sync directly", [
                'error' => $e->getMessage()
            ]);
            // Run sync in background using register_shutdown_function
            register_shutdown_function(function () use ($stockId) {
                try {
                    $this->syncStockToWebsite($stockId);
                } catch (\Exception $e) {
                    Log::error("StockWebsiteSyncService: Error in fallback shutdown function sync for Stock ID {$stockId}", [
                        'error' => $e->getMessage()
                    ]);
                }
            });
        }
    }
}
