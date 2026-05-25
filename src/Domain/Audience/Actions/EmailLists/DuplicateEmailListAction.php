<?php

namespace Spatie\Mailcoach\Domain\Audience\Actions\EmailLists;

use Spatie\Mailcoach\Domain\Audience\Models\EmailList;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;

class DuplicateEmailListAction
{
    use UsesMailcoachModels;

    public function execute(EmailList $emailList, ?string $newName = null): EmailList
    {
        $duplicateEmailList = $emailList->replicateQuietly([
            'uuid',
        ]);

        $duplicateEmailList->name = $newName ?? __mc('Duplicate of').' '.$emailList->name;
        $duplicateEmailList->save();

        return $duplicateEmailList->refresh();
    }
}
