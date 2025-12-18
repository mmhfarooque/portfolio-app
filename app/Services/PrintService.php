<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PrintService
{
    protected string $baseUrl = 'https://api.printful.com';
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.printful.api_key');
    }

    /**
     * Check if Printful is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get available print products.
     */
    public function getProducts(): array
    {
        if (!$this->isConfigured()) {
            return $this->getDefaultProducts();
        }

        return Cache::remember('printful_products', 3600, function () {
            try {
                $response = $this->request('GET', '/products');

                if ($response->successful()) {
                    return $response->json('result') ?? [];
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to fetch Printful products: ' . $e->getMessage());
            }

            return $this->getDefaultProducts();
        });
    }

    /**
     * Get product variants (sizes, etc.).
     */
    public function getProductVariants(int $productId): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        return Cache::remember("printful_product_{$productId}", 3600, function () use ($productId) {
            try {
                $response = $this->request('GET', "/products/{$productId}");

                if ($response->successful()) {
                    return $response->json('result.variants') ?? [];
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to fetch Printful product variants: " . $e->getMessage());
            }

            return [];
        });
    }

    /**
     * Calculate price for a product variant.
     */
    public function getPrice(int $variantId): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->request('POST', '/orders/estimate-costs', [
                'recipient' => [
                    'country_code' => 'US',
                    'state_code' => 'CA',
                    'city' => 'Los Angeles',
                    'zip' => '90001',
                ],
                'items' => [
                    [
                        'variant_id' => $variantId,
                        'quantity' => 1,
                    ],
                ],
            ]);

            if ($response->successful()) {
                return $response->json('result.costs');
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to estimate Printful price: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Create a mockup for a photo on a product.
     */
    public function createMockup(int $productId, string $imageUrl): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->request('POST', "/mockup-generator/create-task/{$productId}", [
                'variant_ids' => [1], // Default variant
                'files' => [
                    [
                        'placement' => 'default',
                        'image_url' => $imageUrl,
                    ],
                ],
            ]);

            if ($response->successful()) {
                return $response->json('result');
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to create Printful mockup: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get store info.
     */
    public function getStoreInfo(): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        return Cache::remember('printful_store', 3600, function () {
            try {
                $response = $this->request('GET', '/stores');

                if ($response->successful()) {
                    $stores = $response->json('result');
                    return $stores[0] ?? null;
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to fetch Printful store info: " . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Make API request to Printful.
     */
    protected function request(string $method, string $endpoint, array $data = [])
    {
        $url = $this->baseUrl . $endpoint;

        $request = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ]);

        if ($method === 'GET') {
            return $request->get($url, $data);
        }

        return $request->post($url, $data);
    }

    /**
     * Get default product options (when API is not configured).
     */
    protected function getDefaultProducts(): array
    {
        return [
            // Physical prints
            [
                'id' => 'print_8x10',
                'name' => '8x10" Fine Art Print',
                'description' => 'Museum-quality archival print on premium matte paper',
                'price' => 29.99,
                'image' => null,
                'type' => 'print',
            ],
            [
                'id' => 'print_11x14',
                'name' => '11x14" Fine Art Print',
                'description' => 'Museum-quality archival print on premium matte paper',
                'price' => 49.99,
                'image' => null,
                'type' => 'print',
            ],
            [
                'id' => 'print_16x20',
                'name' => '16x20" Fine Art Print',
                'description' => 'Museum-quality archival print on premium matte paper',
                'price' => 79.99,
                'image' => null,
                'type' => 'print',
            ],
            [
                'id' => 'canvas_16x20',
                'name' => '16x20" Canvas Gallery Wrap',
                'description' => 'Premium canvas stretched on solid wood frame',
                'price' => 119.99,
                'image' => null,
                'type' => 'canvas',
            ],
            [
                'id' => 'canvas_24x36',
                'name' => '24x36" Canvas Gallery Wrap',
                'description' => 'Premium canvas stretched on solid wood frame',
                'price' => 199.99,
                'image' => null,
                'type' => 'canvas',
            ],
            [
                'id' => 'metal_12x18',
                'name' => '12x18" Metal Print',
                'description' => 'HD aluminum print with vibrant colors',
                'price' => 149.99,
                'image' => null,
                'type' => 'metal',
            ],
            // Digital licenses
            [
                'id' => 'license_personal',
                'name' => 'Personal Use License',
                'description' => 'High-resolution download for personal, non-commercial use. Includes 5 downloads.',
                'price' => 19.99,
                'image' => null,
                'type' => 'license',
                'license_type' => 'personal',
            ],
            [
                'id' => 'license_commercial',
                'name' => 'Commercial License',
                'description' => 'Use in commercial projects, marketing, and publications. Includes 10 downloads.',
                'price' => 99.99,
                'image' => null,
                'type' => 'license',
                'license_type' => 'commercial',
            ],
            [
                'id' => 'license_extended',
                'name' => 'Extended Commercial License',
                'description' => 'Unlimited commercial use, including merchandise and products for resale.',
                'price' => 299.99,
                'image' => null,
                'type' => 'license',
                'license_type' => 'extended',
            ],
        ];
    }
}
