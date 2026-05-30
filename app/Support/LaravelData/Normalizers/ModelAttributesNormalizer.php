<?php

namespace App\Support\LaravelData\Normalizers;

use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Normalizers\Normalizer;

class ModelAttributesNormalizer implements Normalizer
{
    public function normalize(mixed $value): ?array
    {
        if (! $value instanceof Model) {
            return null;
        }

        return $value->attributesToArray();
    }
}
