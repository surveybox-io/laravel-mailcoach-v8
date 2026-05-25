<form
    method="POST"
    class="card-grid"
    wire:submit="save"
    @keydown.prevent.window.cmd.s="$wire.call('save')"
    @keydown.prevent.window.ctrl.s="$wire.call('save')"
>
    <x-mailcoach::alert type="help">
        <p>
            {{ __mc('Mailcoach can create a website that displays the content of each mail you send to this list. This way, people not subscribed to your list can still read your content.') }}
        </p>
    </x-mailcoach::alert>
    <x-mailcoach::fieldset :legend="__mc('Settings')" card>
        <x-mailcoach::checkbox-field
            :label="__mc('Enable website')"
            name="has_website"
            wire:model="has_website"
        />
        <x-mailcoach::checkbox-field
            :label="__mc('Show a subscription form')"
            name="show_subscription_form_on_website"
            wire:model="show_subscription_form_on_website"
        />
        <div class="form-field">
            <label class="label" for="website_slug">{{__mc('Website URL')}}</label>
            <div class="flex items-center">
                <span class="select-none pr-3 h-10 flex flex-shrink-0 items-center text-blue-dark font-medium">{{ route('mailcoach.website', '') }}/</span>
                <input id="website_slug" class="input rounded-r-none" placeholder="/" type="text" name="website_slug" wire:model.defer="website_slug" />
                @if ($has_website)
                    <a class="link ml-2" x-data x-tooltip="'{{ __mc('View website') }}'" href="{{ $emailList->websiteUrl() }}" target="_blank">
                        <x-heroicon-s-arrow-top-right-on-square class="w-4" />
                    </a>
                @endif
            </div>
            @error('emailList.website_slug')
                <p class="form-error" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </x-mailcoach::fieldset>
    <x-mailcoach::fieldset card :legend="__mc('Customization')">
        <x-mailcoach::color-field
            :label="__mc('Primary Color')"
            name="website_primary_color"
            wire:model="website_primary_color"
        />
        <div class="form-field">
            @error('website_theme')
                <p class="form-error">{{ $message }}</p>
            @enderror
            <label class="label label-required" for="website_theme">
                {{ __mc('Style') }}
            </label>
            <div class="grid gap-3 items-start">
                <x-mailcoach::radio-field
                    name="website_theme"
                    option-value="default"
                    wire:model="website_theme"
                    :label="__mc('Default')"
                />
                <x-mailcoach::radio-field
                    name="website_theme"
                    option-value="serif"
                    wire:model="website_theme"
                    :label="__mc('Serif')"
                />
                <x-mailcoach::radio-field
                    name="website_theme"
                    option-value="typewriter"
                    wire:model="website_theme"
                    :label="__mc('Typewriter')"
                />
            </div>
        </div>
        <div class="gap-6">
            <div>
                <label class="label" for="image">
                    Header Image
                </label>
            </div>
            <div class="mt-2 max-w-sm">
                <x-filepond::upload
                    :acceptedFileTypes="['image/png', 'image/jpeg']"
                    :fileValidateTypeLabelExpectedTypes="__mc('Upload a .jpg or .png file')"
                    wire:model="image"
                    maxFileSize="2MB"
                    :placeholder="__mc('Drag & Drop your file or <span class=\'filepond--label-action\'> Browse </span>')"
                />
                @error('image')
                <p class="form-error" role="alert">{{ $message }}</p>
                @enderror
                <x-mailcoach::alert type="info" full class="text-xs mt-3">
                    <span class="text-xs">{!! __mc('This image will be displayed at the top of your website.<br/>The maximum size is 2MB.') !!}</span>
                </x-mailcoach::alert>
            </div>
        </div>
        <x-mailcoach::text-field
            :label="__mc('Website Title')"
            wire:model="website_title"
            name="website_title"
        />
        <x-mailcoach::markdown-field
            :label="__mc('Intro')"
            name="website_intro"
            wire:model="website_intro"
            :help="__mc('This text will be displayed at the top of the page.')"
        />
    </x-mailcoach::fieldset>
    <x-mailcoach::card class="flex items-center gap-6" buttons>
        <x-mailcoach::button :label="__mc('Save')" />
        @if ($dirty)
            <x-mailcoach::alert class="text-xs sm:text-base" type="info">{{ __mc('You have unsaved changes') }}</x-mailcoach::alert>
        @else
            <div wire:key="dirty" wire:dirty>
                <x-mailcoach::alert class="text-xs sm:text-base" type="info">{{ __mc('You have unsaved changes') }}</x-mailcoach::alert>
            </div>
        @endif
    </x-mailcoach::card>
</form>

