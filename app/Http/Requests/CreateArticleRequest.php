<?php

namespace App\Http\Requests;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CreateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Article::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
            'title' => ['required', 'string', 'max:255'],
            'description' => ['string'],
            'thumbnail' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10000'],
            'content' => ['required', 'json'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $tags = $this->input('tags');

        if (is_array($tags) && isset($tags[0]) && is_string($tags[0])) {
            $decoded = json_decode($tags[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['tags' => array_map('intval', $decoded)]);
            }
        }
    }

    protected function passedValidation()
    {
        $baseSlug = Str::slug($this->title);
        $uniquePart = substr((string) Str::uuid(), 0, 4);

        $this->merge([
            'school_id' => Auth::user()->school_id,
            'slug' => $baseSlug.'-'.$uniquePart,
        ]);
    }
}
