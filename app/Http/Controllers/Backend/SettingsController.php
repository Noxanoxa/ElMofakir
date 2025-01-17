<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Spatie\Valuestore\Valuestore;

class SettingsController extends Controller
{
    public function __construct()
    {
        if (\auth()->check()){
            $this->middleware('auth');
        } else {
            return view('backend.auth.login');
        }
    }

    public function index()
    {
        if (!\auth()->user()->ability('admin', 'manage_settings,show_settings')) {
            return redirect('admin/index');
        }

        $section = (request('section')!= '') ? request('section') :  'general' ;
        $settings_sections = Setting::select('section', 'section_en')->distinct()->get();
        $settings = Setting::where('section', $section)->orWhere('section_en', $section)->get();

        return view('backend.settings.index', compact('section', 'settings_sections', 'settings'));

    }

    public function update(Request $request, $id)
    {
        for ($i = 0; $i < count($request->id); $i++) {
            $input['value'] = isset($request->value[$i]) ? $request->value[$i] : null;
            Setting::whereId($request->id[$i])->first()->update($input);
        }
        $this->generateCache();

        return redirect()->route('admin.settings.index')->with([
            'message' => 'Settings updated successfully',
            'alert-type' => 'success'
        ]);
    }

    private function generateCache()
    {
        $settings = Valuestore::make(config_path('settings.json'));
        Setting::whereLang('ar')->each(function ($item) use ($settings) {
            $settings->put($item->key, $item->value);
        });

        $settings_en = Valuestore::make(config_path('settings_en.json'));
        Setting::whereLang('en')->each(function ($item) use ($settings_en) {
            $settings_en->put($item->key, $item->value);
        });
    }

}
