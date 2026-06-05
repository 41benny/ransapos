<?php

namespace App\Observers;

use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class ActivityLogObserver
{
    public function created(Model $model): void
    {
        $label = ActivityLogger::labelFor($model);
        $name = ActivityLogger::identifier($model);

        ActivityLogger::log(
            'created',
            trim("Menambah {$label} {$name}"),
            $model,
            ['attributes' => $this->filterAttributes($model, $model->getAttributes())],
        );
    }

    public function updated(Model $model): void
    {
        $changes = $this->filterAttributes($model, $model->getChanges());

        // Hanya field yang benar-benar berubah (selain timestamp/sensitif).
        if (empty($changes)) {
            return;
        }

        $original = array_intersect_key($model->getOriginal(), $changes);

        $label = ActivityLogger::labelFor($model);
        $name = ActivityLogger::identifier($model);

        ActivityLogger::log(
            'updated',
            trim("Mengubah {$label} {$name}"),
            $model,
            [
                'old'        => $original,
                'attributes' => $changes,
            ],
        );
    }

    public function deleted(Model $model): void
    {
        $label = ActivityLogger::labelFor($model);
        $name = ActivityLogger::identifier($model);

        ActivityLogger::log(
            'deleted',
            trim("Menghapus {$label} {$name}"),
            $model,
            ['attributes' => $this->filterAttributes($model, $model->getOriginal())],
        );
    }

    /**
     * Buang field sensitif/noise dari payload.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function filterAttributes(Model $model, array $attributes): array
    {
        return collect($attributes)
            ->except(ActivityLogger::ignoredAttributes($model))
            ->all();
    }
}
