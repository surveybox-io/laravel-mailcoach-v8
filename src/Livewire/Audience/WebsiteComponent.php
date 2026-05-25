<?php

namespace Spatie\Mailcoach\Livewire\Audience;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\LivewireFilepond\WithFilePond;
use Spatie\Mailcoach\Domain\Audience\Models\EmailList;
use Spatie\Mailcoach\Domain\Shared\Traits\UsesMailcoachModels;
use Spatie\Mailcoach\Mailcoach;
use Spatie\Mailcoach\MainNavigation;

class WebsiteComponent extends Component
{
    use UsesMailcoachModels;
    use WithFilePond;
    use WithFileUploads;

    /** @var \Illuminate\Http\UploadedFile|string */
    public $image;

    public EmailList $emailList;

    public bool $dirty = false;

    public bool $has_website = false;

    public bool $show_subscription_form_on_website = false;

    public ?string $website_slug;

    public ?string $website_title;

    public ?string $website_intro;

    public ?string $website_primary_color;

    public ?string $website_theme;

    protected function rules(): array
    {
        $rules = [
            'has_website' => ['boolean'],
            'show_subscription_form_on_website' => ['boolean'],
            'website_slug' => ['nullable', Rule::unique(Mailcoach::getDatabaseConnection().'.'.self::getEmailListTableName(), 'website_slug')
                ->ignore($this->emailList->id)],
            'website_title' => ['nullable'],
            'website_intro' => ['nullable'],
            'website_primary_color' => ['nullable'],
            'website_theme' => ['nullable'],
        ];

        if ($this->image instanceof TemporaryUploadedFile) {
            $rules['image'] = ['', 'image', 'max:2048'];
        }

        return $rules;
    }

    public function mount(EmailList $emailList)
    {
        $this->emailList = $emailList;
        $this->fill($emailList->toArray());
        $this->image = $emailList->getFirstMediaUrl('header');

        app(MainNavigation::class)->activeSection()
            ->add($this->emailList->name, route('mailcoach.emailLists.website', $this->emailList));
    }

    public function remove($property, $filename): void
    {
        $this->$property = null;
    }

    public function save()
    {
        $this->validate();

        if ($this->image instanceof TemporaryUploadedFile) {
            $path = $this->handleUpload();

            if (! $path) {
                notifyError('Upload failed. Please try again');

                return;
            }

            $this
                ->emailList
                ->addMedia($path)
                ->toMediaLibrary('header', config('mailcoach.website_disk'));
        } elseif (is_null($this->image)) {
            $this->emailList->clearMediaCollection('header');
        }

        /** Make sure to enable form subscriptions when form is shown on website */
        if ($this->show_subscription_form_on_website) {
            $this->emailList->allow_form_subscriptions = true;
        }

        $this->emailList->fill(Arr::except($this->all(), ['emailList', 'image', 'dirty']));

        $this->emailList->save();

        notify(__mc('Website settings for list :emailList were updated', ['emailList' => $this->emailList->name]));

        $this->dirty = false;
    }

    public function updated()
    {
        $this->dirty = true;
    }

    public function render(): View
    {
        return view('mailcoach::app.emailLists.settings.website')
            ->layout('mailcoach::app.emailLists.layouts.emailList', [
                'title' => __mc('Website'),
                'emailList' => $this->emailList,
            ]);
    }

    protected function handleUpload(): ?string
    {
        $diskName = config('mailcoach.tmp_disk');

        $relativePath = $this->image->store('uploads', [
            'disk' => $diskName,
        ]);

        if (! $relativePath) {
            return $relativePath;
        }

        return Storage::disk($diskName)->path($relativePath);
    }
}
