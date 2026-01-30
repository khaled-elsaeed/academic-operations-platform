<?php

namespace App\Policies;

use App\Services\SettingService;
use App\Exceptions\BusinessValidationException;

class FeatureAvailabilityPolicy
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Check if a feature is available and throw exception if not.
     *
     * @param string $feature The feature name (e.g., 'enrollment')
     * @param string|null $action The specific action (e.g., 'create', 'delete')
     * @throws BusinessValidationException
     */
    public function checkAvailable(string $feature, ?string $action = null): void
    {
        if (!$this->isAvailable($feature, $action)) {
            $message = ucfirst($feature) . ' is not available';
            if ($action) {
                $message .= " for {$action} action";
            }
            $message .= '.';
            throw new BusinessValidationException($message);
        }
    }

    /**
     * Check if a feature is available.
     *
     * @param string $feature The feature name (e.g., 'enrollment')
     * @param string|null $action The specific action (e.g., 'create', 'delete')
     * @return bool
     */
    public function isAvailable(string $feature, ?string $action = null): bool
    {
        $settings = $this->getFeatureSettings($feature);

        $enabled = isset($settings["enable_{$feature}"]) ? (int) $settings["enable_{$feature}"] : 0;
        if ($enabled !== 1) {
            return false;
        }

        if ($action) {
            $allowed = isset($settings["allow_{$action}_{$feature}"]) ? (int) $settings["allow_{$action}_{$feature}"] : 0;
            if ($allowed !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get settings for a specific feature.
     *
     * @param string $feature
     * @return array
     */
    protected function getFeatureSettings(string $feature): array
    {
        // Assuming SettingService has a method to get feature settings
        // If not, we can modify SettingService or use allAssoc
        return $this->settingService->allAssoc();
    }
}