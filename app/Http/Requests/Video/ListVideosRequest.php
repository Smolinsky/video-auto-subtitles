<?php

namespace App\Http\Requests\Video;

use App\Dto\Video\ListVideoFilterDto;
use App\Dto\Video\ListVideosDto;
use App\Enums\VideoProcessingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListVideosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(VideoProcessingStatus::class)],
            'search' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function getDto(): ListVideosDto
    {
        $validated = $this->validated();
        $search = isset($validated['search']) ? trim((string) $validated['search']) : null;

        return new ListVideosDto(
            user: $this->user(),
            filters: new ListVideoFilterDto(
                status: isset($validated['status']) ? (string) $validated['status'] : null,
                search: $search !== '' ? $search : null,
            ),
        );
    }
}
