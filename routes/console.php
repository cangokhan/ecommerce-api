<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// XML'den ürün import schedule
// Her saat başı aktif kaynakları kontrol eder ve import eder
Schedule::call(function () {
    $sources = \App\Models\XmlSource::where('is_active', true)->get();
    
    foreach ($sources as $source) {
        if (!$source->shouldImport()) {
            continue;
        }

        // Tercih edilen import saati varsa kontrol et
        if ($source->preferred_import_time) {
            $preferredTime = \Carbon\Carbon::parse($source->preferred_import_time);
            $currentTime = now();
            
            // Sadece tercih edilen saatte import et (saat ve dakika kontrolü)
            if ($currentTime->format('H:i') === $preferredTime->format('H:i')) {
                \App\Jobs\ImportProductsFromXmlJob::dispatch($source->id);
            }
        } else {
            // Tercih edilen saat yoksa direkt import et
            \App\Jobs\ImportProductsFromXmlJob::dispatch($source->id);
        }
    }
})
    ->hourly()
    ->withoutOverlapping()
    ->onFailure(function () {
        \Log::error('Scheduled XML product import failed');
    })
    ->onSuccess(function () {
        \Log::info('Scheduled XML product import check completed');
    });
