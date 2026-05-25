<?php

namespace Spatie\Mailcoach\Http\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Mailcoach\Domain\Audience\Models\EmailList;
use Spatie\Mailcoach\Domain\Audience\Rules\EmailListSubscriptionRule;

class StoreSubscriberRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', config('mailcoach.audience.email_validation_rule', 'email:strict,dns'), Rule::when($this->get('strict', false), [new EmailListSubscriptionRule($this->emailList())])],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'extra_attributes' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:255'],
        ];
    }

    public function emailList(): EmailList
    {
        return request()->route()->parameter('emailList');
    }

    public function subscriberAttributes(): array
    {
        return [
            'first_name' => $this->input('first_name'),
            'last_name' => $this->input('last_name'),
            'extra_attributes' => $this->input('extra_attributes'),
        ];
    }

    public function messages()
    {
        return [
            'email.unique' => 'There already is a subscriber with this email.',
        ];
    }
}
