<?php

namespace Spatie\Mailcoach\Domain\Audience\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Mailcoach\Domain\Audience\Models\Tag;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Mailcoach;

class ConsolidateTagsCommand extends Command
{
    use UsesMailcoachModels;

    protected $signature = 'mailcoach:consolidate-tags';

    public $description = 'Consolidate duplicate tags of each email list.';

    public function handle(): void
    {
        $this->getOutput()->progressStart(self::getTagClass()::count());

        self::getTagClass()::query()
            ->each(function (Tag $tag) {
                $this->getOutput()->progressAdvance();

                if (! $tag->fresh()) {
                    return;
                }

                $duplicateTags = self::getTagClass()::query()
                    ->where('name', $tag->name)
                    ->where('type', $tag->type)
                    ->where('email_list_id', $tag->email_list_id)
                    ->where('id', '!=', $tag->id)
                    ->get();

                if (! $duplicateTags->count()) {
                    return;
                }
                DB::connection(Mailcoach::getDatabaseConnection())
                    ->table('mailcoach_email_list_subscriber_tags')
                    ->whereIn('tag_id', $duplicateTags->pluck('id'))
                    ->update([
                        'tag_id' => $tag->id,
                    ]);

                $duplicateTags->each->delete();
            });

        $this->getOutput()->progressFinish();
    }
}
