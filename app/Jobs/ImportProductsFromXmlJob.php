<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\User;
use App\Models\XmlSource;
use App\Services\XmlProductService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportProductsFromXmlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $xmlSourceId,
        public ?int $adminUserId = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(XmlProductService $xmlService): void
    {
        $xmlSource = XmlSource::find($this->xmlSourceId);

        if (!$xmlSource) {
            Log::error('XML source not found', ['id' => $this->xmlSourceId]);
            return;
        }

        if (!$xmlSource->is_active) {
            Log::info('XML source is inactive, skipping import', ['id' => $this->xmlSourceId]);
            return;
        }

        Log::info('Starting XML product import', [
            'source_id' => $xmlSource->id,
            'source_name' => $xmlSource->name,
            'url' => $xmlSource->url,
        ]);

        // XML'i çek
        $xml = $xmlService->fetchXmlFromUrl($xmlSource->url);

        if (!$xml) {
            $error = 'Failed to fetch XML from URL';
            Log::error($error, ['source_id' => $xmlSource->id]);
            $xmlSource->markAsFailed($error);
            return;
        }

        // Ürünleri parse et
        $products = $xmlService->parseProducts($xml);

        if (empty($products)) {
            $error = 'No products found in XML';
            Log::warning($error, ['source_id' => $xmlSource->id]);
            $xmlSource->markAsFailed($error);
            return;
        }

        // Admin kullanıcı ID'sini al (varsayılan olarak ilk admin)
        $adminUser = $this->adminUserId 
            ? User::find($this->adminUserId)
            : User::where('role', 'admin')->first();

        if (!$adminUser) {
            $error = 'No admin user found for product import';
            Log::error($error);
            $xmlSource->markAsFailed($error);
            return;
        }

        $created = 0;
        $updated = 0;
        $errors = 0;

        // Ürünleri ekle veya güncelle
        foreach ($products as $productData) {
            try {
                // External ID yoksa atla
                if (empty($productData['external_id'])) {
                    Log::warning('Product skipped: missing external_id', $productData);
                    $errors++;
                    continue;
                }

                // Ürün adı yoksa atla
                if (empty($productData['name'])) {
                    Log::warning('Product skipped: missing name', $productData);
                    $errors++;
                    continue;
                }

                // External ID'yi source ile birleştir (aynı ID farklı kaynaklarda olabilir)
                $uniqueExternalId = $xmlSource->id . '_' . $productData['external_id'];

                // Ürünü bul veya oluştur
                $product = Product::updateOrCreate(
                    ['external_id' => $uniqueExternalId],
                    [
                        'name' => $productData['name'],
                        'description' => $productData['description'] ?? null,
                        'price' => $productData['price'] ?? 0,
                        'stock' => $productData['stock'] ?? 0,
                        'status' => 'active',
                        'created_by' => $adminUser->id,
                    ]
                );

                if ($product->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Exception $e) {
                Log::error('Error importing product', [
                    'product_data' => $productData,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
            }
        }

        $totalProcessed = $created + $updated;
        $xmlSource->markAsImported($totalProcessed);

        Log::info('XML product import completed', [
            'source_id' => $xmlSource->id,
            'source_name' => $xmlSource->name,
            'url' => $xmlSource->url,
            'total' => count($products),
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ]);
    }
}

