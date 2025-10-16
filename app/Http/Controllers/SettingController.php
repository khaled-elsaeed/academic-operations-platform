<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SettingService;
use App\Models\Setting;

class SettingController extends Controller
{
    protected $service;

    public function __construct(SettingService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $settings = $this->service->all();
        return view('settings.index', compact('settings'));
    }

    /**
     * Return enrollment settings grouped for the UI.
     */
    public function enrollment()
    {
        $data = $this->service->getEnrollmentSettings();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show($key)
    {
        $setting = $this->service->get($key);
        return response()->json($setting);
    }

    public function store(Request $request)
    {
        $setting = $this->service->set(
            $request->input('key'),
            $request->input('value'),
            $request->input('type'),
            $request->input('group')
        );
        return response()->json($setting);
    }

    public function update(Request $request, $key)
    {
        $setting = $this->service->set(
            $key,
            $request->input('value'),
            $request->input('type'),
            $request->input('group')
        );
        return response()->json($setting);
    }

    /**
     * Update enrollment settings from form payload.
     */
    public function updateEnrollment(Request $request)
    {
        $payload = $request->except(['_token', '_method']);

    // Normalize checkbox values from inputs (0/1)
    $normalized = [];
    $normalized['enable_enrollment'] = (int) $request->input('enable_enrollment', 0);
    $normalized['allow_create_enrollment'] = (int) $request->input('allow_create_enrollment', 0);
    $normalized['allow_delete_enrollment'] = (int) $request->input('allow_delete_enrollment', 0);

        // If master switch is off, subordinate options must be disabled
        if ($normalized['enable_enrollment'] == 0) {
            $normalized['allow_create_enrollment'] = 0;
            $normalized['allow_delete_enrollment'] = 0;
        }

        $updated = $this->service->updateMany($normalized);

        return response()->json(["success" => true, "updated" => array_keys($updated)]);
    }

    public function destroy($key)
    {
        $this->service->delete($key);
        return response()->json(['success' => true]);
    }
}
