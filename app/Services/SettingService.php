<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    public function all()
    {
        return Setting::all();
    }

    /**
     * Return settings as an associative array keyed by setting key.
     * Values will be returned using the model accessor (properly cast).
     *
     * @return array
     */
    public function allAssoc(): array
    {
        return Setting::all()->mapWithKeys(function ($s) {
            return [$s->key => $s->value];
        })->toArray();
    }

    public function get($key)
    {
        return Setting::where('key', $key)->first();
    }

    public function set($key, $value, $type = null, $group = null)
    {
        $data = ['value' => $value];
        if ($type !== null) {
            $data['type'] = $type;
        }
        if ($group !== null) {
            $data['group'] = $group;
        }

        $setting = Setting::updateOrCreate(['key' => $key], $data);
        return $setting;
    }

    /**
     * Update multiple settings provided as [key => value].
     * Optionally accepts an array of metadata for type/group but the
     * simple form is used by the UI.
     *
     * @param array $data
     * @return array Updated settings as key => Setting model
     */
    public function updateMany(array $data): array
    {
        $updated = [];
        foreach ($data as $key => $value) {
            // Skip empty keys
            if ($key === '_token' || $key === '_method') {
                continue;
            }

            $updated[$key] = $this->set($key, $value);
        }

        return $updated;
    }

    public function delete($key)
    {
        return Setting::where('key', $key)->delete();
    }

    /**
     * Return enrollment-related settings as an associative array.
     *
     * @return array
     */
    public function getEnrollmentSettings(): array
    {
        $settings = Setting::where('group', 'enrollment')->get();

        return $settings->mapWithKeys(function ($s) {
            return [$s->key => $s->value];
        })->toArray();
    }
}
