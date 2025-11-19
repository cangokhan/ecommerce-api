<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class XmlProductService
{
    /**
     * Fetch and parse XML from URL
     */
    public function fetchXmlFromUrl(string $url): ?SimpleXMLElement
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                Log::error('Failed to fetch XML', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $xml = simplexml_load_string($response->body());

            if ($xml === false) {
                Log::error('Failed to parse XML', [
                    'url' => $url,
                    'errors' => libxml_get_errors(),
                ]);
                return null;
            }

            return $xml;
        } catch (\Exception $e) {
            Log::error('Exception while fetching XML', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Parse products from XML
     * 
     * XML yapısına göre bu metod özelleştirilebilir
     * Örnek XML yapısı:
     * <products>
     *   <product>
     *     <id>123</id>
     *     <name>Product Name</name>
     *     <description>Description</description>
     *     <price>99.99</price>
     *     <stock>100</stock>
     *   </product>
     * </products>
     */
    public function parseProducts(SimpleXMLElement $xml): array
    {
        $products = [];

        // XML yapısına göre bu kısmı özelleştirin
        // Örnek: $xml->product veya $xml->products->product gibi
        if (isset($xml->product)) {
            foreach ($xml->product as $productXml) {
                $products[] = $this->parseProductItem($productXml);
            }
        } elseif (isset($xml->products->product)) {
            foreach ($xml->products->product as $productXml) {
                $products[] = $this->parseProductItem($productXml);
            }
        } elseif (isset($xml->item)) {
            foreach ($xml->item as $productXml) {
                $products[] = $this->parseProductItem($productXml);
            }
        }

        return $products;
    }

    /**
     * Parse single product item from XML
     */
    protected function parseProductItem(SimpleXMLElement $item): array
    {
        // XML yapısına göre bu kısmı özelleştirin
        return [
            'external_id' => (string) ($item->id ?? $item->product_id ?? $item->code ?? ''),
            'name' => (string) ($item->name ?? $item->title ?? $item->product_name ?? ''),
            'description' => (string) ($item->description ?? $item->desc ?? ''),
            'price' => (float) ($item->price ?? $item->amount ?? 0),
            'stock' => (int) ($item->stock ?? $item->quantity ?? $item->qty ?? 0),
        ];
    }
}

