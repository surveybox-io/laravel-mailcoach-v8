<form
    class="card-grid"
    method="POST"
    wire:submit="save"
    @keydown.prevent.window.cmd.s="$wire.call('save')"
    @keydown.prevent.window.ctrl.s="$wire.call('save')"
>
    <x-mailcoach::fieldset card :legend="__mc('Settings')">
        <x-mailcoach::text-field :label="__mc('Name')" name="form.name" wire:model="form.name" required/>

        <div class="form-field max-w-full">
            <label class="label" for="form.campaigns_feed_enabled">{{ __mc('Publication') }}</label>
            <x-mailcoach::checkbox-field
                :label="__mc('Make RSS feed publicly available')"
                name="form.campaigns_feed_enabled"
                wire:model.live="form.campaigns_feed_enabled"
            />
            @if ($this->form->campaigns_feed_enabled)
                <x-mailcoach::alert type="info" class="mt-2" full>
                    {{ __mc('Your public feed will be available at') }}
                    <a class="text-sm link" target="_blank" href="{{$emailList->feedUrl()}}">{{$emailList->feedUrl()}}</a>
                </x-mailcoach::alert>
            @endif
        </div>
    </x-mailcoach::fieldset>

    <x-mailcoach::fieldset card :legend="__mc('Sender')">

        <div class="grid sm:grid-cols-2 gap-6">
            <x-mailcoach::text-field :label="__mc('From email')" name="form.default_from_email" wire:model.lazy="form.default_from_email"
                        type="email" required/>

            <x-mailcoach::text-field :label="__mc('From name')" name="form.default_from_name" wire:model.lazy="form.default_from_name"/>

            <x-mailcoach::text-field
                :label="__mc('Reply-to email')"
                name="form.default_reply_to_email"
                :help="__mc('Use a comma separated list to send replies to multiple email addresses.')"
                wire:model.lazy="form.default_reply_to_email"
            />

            <x-mailcoach::text-field
                :label="__mc('Reply-to name')"
                name="form.default_reply_to_name"
                :help="__mc('Use a comma separated list to send replies to multiple email addresses.')"
                wire:model.lazy="form.default_reply_to_name"
            />
        </div>
    </x-mailcoach::fieldset>

    <x-mailcoach::fieldset card :legend="__mc('Mailers')">
        <x-mailcoach::alert type="help" :full="false">
            {{ __mc('Select a mailer for each of the functionalities of Mailcoach. If you leave them empty, the default mailer or the mailer set in your configuration file will be used.') }}
        </x-mailcoach::alert>

        @if(count(config('mail.mailers')) > 1)
            <x-mailcoach::select-field
                name="campaign_mailer"
                :options="array_combine(array_keys(config('mail.mailers')), array_keys(config('mail.mailers')))"
                :placeholder="__mc('Select a mailer')"
                :clearable="true"
                wire:model="form.campaign_mailer"
                :label="__mc('Campaign mailer')"
            />

            <x-mailcoach::select-field
                name="automation_mailer"
                :options="array_combine(array_keys(config('mail.mailers')), array_keys(config('mail.mailers')))"
                :placeholder="__mc('Select a mailer')"
                :clearable="true"
                wire:model="form.automation_mailer"
                :label="__mc('Automation mailer')"
            />

            <x-mailcoach::select-field
                name="transactional_mailer"
                :options="array_combine(array_keys(config('mail.mailers')), array_keys(config('mail.mailers')))"
                :placeholder="__mc('Select a mailer')"
                :clearable="true"
                wire:model="form.transactional_mailer"
                :label="__mc('Transactional mailer')"
            />
        @else
            <x-mailcoach::alert type="info">{{ __mc('No mailers set.') }}</x-mailcoach::alert>
        @endif
    </x-mailcoach::fieldset>

    <x-mailcoach::fieldset card :legend="__mc('Email Notifications')">
        <div class="grid gap-3">
            <x-mailcoach::checkbox-field :label="__mc('Confirmation when a campaign has finished sending to this list')"
                            name="form.report_campaign_sent" wire:model.live="form.report_campaign_sent"/>
            <x-mailcoach::checkbox-field
                :label="__mc('Summary of opens, clicks & bounces a day after a campaign has been sent to this list')"
                name="form.report_campaign_summary" wire:model.live="form.report_campaign_summary"/>
            <x-mailcoach::checkbox-field :label="__mc('Weekly summary on the subscriber growth of this list')"
                            name="form.report_email_list_summary" wire:model.live="form.report_email_list_summary"/>
        </div>

        @if ($this->form->report_campaign_sent || $this->form->report_campaign_summary || $this->form->report_email_list_summary)
            <x-mailcoach::text-field
                :help="__mc('Which email address(es) should the notifications be sent to?')"
                :placeholder="__mc('Email(s) comma separated')"
                :label="__mc('Email')"
                name="form.report_recipients"
                wire:model.lazy="form.report_recipients"
            />
        @endif
    </x-mailcoach::fieldset>

    @if (\Illuminate\Support\Facades\Schema::hasColumn(\Spatie\Mailcoach\Mailcoach::getEmailListTableName(), 'extra_attributes'))
        <x-mailcoach::fieldset card :legend="__mc('Attributes')">
            <x-mailcoach::alert class="-mb-4" type="info" full>
                {{ __mc('You can define global attributes here that get merged in when sending an email to this list.') }}
            </x-mailcoach::alert>
            <div class="form-field">
                <div x-data="{ attributes: @entangle('form.extra_attributes').live }">
                    <template x-for="(attribute, index) in attributes" x-bind:key="index">
                        <div class="my-4 flex items-center w-full gap-x-2">
                            <div class="relative w-full flex items-center">
                                <x-mailcoach::text-field wrapper-class="w-full" x-model="attribute.key" name="key"
                                                         :label="__mc('Key')">
                                </x-mailcoach::text-field>
                                <button
                                    type="button"
                                    tabindex="-1"
                                    x-tooltip.on.click="'{{ __mc('Copied!') }}'"
                                    x-clipboard="'@{{ list.' + attribute.key + ' }}'"
                                    class="absolute right-0 mt-8 mr-4 text-sm ml-1 text-gray-500"
                                >
                                    <x-heroicon-s-document-duplicate class="w-4" />
                                </button>
                            </div>
                            <x-mailcoach::text-field
                                wrapper-class="w-full"
                                x-model="attribute.value"
                                name="value"
                                :label="__mc('Value')"
                            />
                            <button
                                x-on:click.prevent="
                                        if (confirm(@js(__mc('Are you sure you want to delete this attribute?')))) {
                                            attributes.splice(index, 1)
                                        }
                                    "
                                class="mt-auto mb-4 pb-px opacity-75 hover:text-red cursor-pointer"
                            >
                                <x-heroicon-s-trash class="w-4" />
                            </button>
                        </div>
                    </template>
                    <div>
                        <x-mailcoach::button-link class="flex items-center gap-x-1" x-on:click.prevent="attributes.push({ key: '', value: '' })">
                            <x-heroicon-s-plus-circle class="w-4" />
                            {{ __mc('Add attribute') }}
                        </x-mailcoach::button-link>
                    </div>
                </div>
            </div>
        </x-mailcoach::fieldset>
    @endif

    <x-mailcoach::api-card
        resource-name="email_list_uuid"
        resource="email list"
        :uuid="$emailList->uuid"
    />

    <x-mailcoach::card class="flex items-center gap-6" buttons>
        <x-mailcoach::button :label="__mc('Save')" />
        @if ($form->dirty)
            <x-mailcoach::alert class="text-xs sm:text-base" type="info">{{ __mc('You have unsaved changes') }}</x-mailcoach::alert>
        @else
            <div wire:key="dirty" wire:dirty>
                <x-mailcoach::alert class="text-xs sm:text-base" type="info">{{ __mc('You have unsaved changes') }}</x-mailcoach::alert>
            </div>
        @endif
    </x-mailcoach::card>
</form>

