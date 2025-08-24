<?php

namespace ESolution\WhatsApp\Console\Commands;

use Illuminate\Console\Command;
use ESolution\WhatsApp\Models\{WhatsAppBroadcast, WhatsAppBroadcastRecipient};
use ESolution\WhatsApp\Jobs\SendBroadcastChunkJob;

class BroadcastRunCommand extends Command
{
    protected $signature = 'whatsapp:broadcast-run {--id=}';
    protected $description = 'Pick scheduled broadcasts and enqueue jobs in chunks';

    public function handle(): int
    {
        $query = WhatsAppBroadcast::query()
            ->when($this->option('id'), fn($q,$id)=>$q->where('id',$id))
            ->whereIn('status', ['scheduled','running'])
            ->where(function($q){ $q->whereNull('scheduled_at')->orWhere('scheduled_at','<=', now()); });

        $count = 0;
        foreach ($query->cursor() as $b) {
            $b->status = 'running'; $b->save();

            $pendings = WhatsAppBroadcastRecipient::where('whatsapp_broadcast_id',$b->id)
                ->whereIn('status', ['pending'])
                ->pluck('id')
                ->toArray();

            if (empty($pendings)) {
                $b->status = 'finished'; $b->save();
                $this->info("Broadcast {$b->id} finished.");
                continue;
            }

            $chunks = array_chunk($pendings, (int)$b->chunk_size);
            foreach ($chunks as $chunk) {
                dispatch((new SendBroadcastChunkJob($b->id, $chunk))
                    ->onConnection(config('whatsapp.queue')));
                $count += count($chunk);
            }
            $this->info("Broadcast {$b->id}: dispatched ".count($pendings)." recipients.");
        }

        $this->info("Total dispatched recipients: {$count}");
        return self::SUCCESS;
    }
}
