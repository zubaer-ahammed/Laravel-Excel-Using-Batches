<?php

namespace Maatwebsite\Excel\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\ImportFailed;
use Maatwebsite\Excel\HasEventBus;
use Maatwebsite\Excel\Reader;
use Throwable;

class AfterImportJob implements ShouldQueue
{
    use Queueable, HasEventBus, Batchable, InteractsWithQueue;

    /**
     * @var WithEvents
     */
    private $import;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @param  object  $import
     * @param  Reader  $reader
     */
    public function __construct($import, Reader $reader)
    {
        $this->import = $import;
        $this->reader = $reader;
    }

    public function handle()
    {
        if(!empty($this->batch())) {

            if ($this->batch()->cancelled()) {
                // Determine if the batch has been cancelled...

                return;
            }

        }

        if ($this->import instanceof ShouldQueue && $this->import instanceof WithEvents) {
            $this->reader->clearListeners();
            $this->reader->registerListeners($this->import->registerEvents());
        }

        $this->reader->afterImport($this->import);
    }

    /**
     * @param  Throwable  $e
     */
    public function failed(Throwable $e)
    {
        if ($this->import instanceof WithEvents) {
            $this->registerListeners($this->import->registerEvents());
            $this->raise(new ImportFailed($e));

            if (method_exists($this->import, 'failed')) {
                $this->import->failed($e);
            }
        }
    }
}
