<?php

namespace App\Console\Commands;

use App\Jobs\ImportProductsFromXmlJob;
use App\Models\XmlSource;
use Illuminate\Console\Command;

class ImportProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import-xml 
                            {--source-id= : XML Source ID to import from}
                            {--all : Import from all active sources}
                            {--admin-id= : Admin user ID to assign products to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from XML sources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $adminId = $this->option('admin-id') ? (int) $this->option('admin-id') : null;

        if ($this->option('all')) {
            // Tüm aktif kaynakları import et
            $sources = XmlSource::where('is_active', true)->get();

            if ($sources->isEmpty()) {
                $this->error('No active XML sources found.');
                return Command::FAILURE;
            }

            $this->info("Found {$sources->count()} active XML source(s).");

            foreach ($sources as $source) {
                if ($source->shouldImport()) {
                    $this->info("Queuing import for: {$source->name} ({$source->url})");
                    ImportProductsFromXmlJob::dispatch($source->id, $adminId);
                } else {
                    $this->warn("Skipping {$source->name} - not ready for import yet.");
                }
            }

            $this->info('All import jobs have been queued.');
        } elseif ($sourceId = $this->option('source-id')) {
            // Belirli bir kaynağı import et
            $source = XmlSource::find($sourceId);

            if (!$source) {
                $this->error("XML source with ID {$sourceId} not found.");
                return Command::FAILURE;
            }

            if (!$source->is_active) {
                $this->warn("XML source '{$source->name}' is inactive.");
            }

            $this->info("Starting product import from: {$source->name} ({$source->url})");
            ImportProductsFromXmlJob::dispatch($source->id, $adminId);
            $this->info('Product import job has been queued.');
        } else {
            $this->error('Please specify --source-id or --all option.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

