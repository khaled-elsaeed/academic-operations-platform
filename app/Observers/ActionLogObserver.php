<?php

namespace App\Observers;


class ActionLogObserver
{
    public function created($model)
    {
        logAction('created', $model, filterSensitive($model->getAttributes()));
    }

    public function updated($model)
    {
        $changes = [
            'old' => filterSensitive($model->getOriginal()),
            'new' => filterSensitive($model->getAttributes()),
            'changed' => filterSensitive($model->getChanges()),
        ];
        logAction('updated', $model, $changes);
    }

    public function deleted($model)
    {
        logAction('deleted', $model, filterSensitive($model->getAttributes()));
    }

} 