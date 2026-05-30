<?php

namespace App\Http\Requests\Video;

use App\Dto\Video\CreateVideoUploadDto;
use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'video' => [
                'required',
                'file',
                'mimes:mp4,mov,avi,webm,mkv',
                'max:'.config('subtitles.maxUploadKb', 102400),
            ],
        ];
    }

    public function getDto(): CreateVideoUploadDto
    {
        return new CreateVideoUploadDto(
            user: $this->user(),
            video: $this->file('video'),
        );
    }
}
