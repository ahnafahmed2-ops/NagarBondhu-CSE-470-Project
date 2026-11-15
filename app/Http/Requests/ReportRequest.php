<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validation shared by creating and editing a report (Feature F1). */
class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership is checked in the controller
    }

    public function rules(): array
    {
        return [
            'category_id'     => ['required', 'integer', 'exists:categories,id'],
            'title'           => ['required', 'string', 'min:5', 'max:120'],
            'description'     => ['required', 'string', 'min:20', 'max:2000'],
            'address'         => ['required', 'string', 'min:3', 'max:200'],
            'ward'            => ['nullable', 'string', 'max:60'],
            'evidence'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_evidence' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Choose what kind of problem this is.',
            'category_id.exists'   => 'Choose one of the listed categories.',
            'title.required'       => 'Give the problem a short title.',
            'title.min'            => 'The title needs at least 5 characters.',
            'title.max'            => 'The title can be at most 120 characters.',
            'description.required' => 'Describe the problem.',
            'description.min'      => 'Add a little more detail (at least 20 characters).',
            'description.max'      => 'The description can be at most 2000 characters.',
            'address.required'     => 'Tell us where it is.',
            'address.min'          => 'Add a road, landmark or area name.',
            'ward.max'             => 'Ward / area can be at most 60 characters.',
            'evidence.image'       => 'The evidence must be an image.',
            'evidence.mimes'       => 'Only JPG, PNG or WebP images are accepted.',
            'evidence.max'         => 'The image must be 5 MB or smaller.',
            'evidence.uploaded'    => 'The image could not be uploaded. It may be larger than the server allows.',
        ];
    }
}
