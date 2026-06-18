<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Department;
use App\Models\BudgetCategory;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'app_name' => Setting::get('app_name', 'Finance Monitoring'),
            'app_logo' => Setting::get('app_logo'),
            'app_favicon' => Setting::get('app_favicon'),
            'procurement_api_key' => Setting::get('procurement_api_key', env('PROCUREMENT_API_KEY')),
            'odoo_url' => Setting::get('odoo_url', config('services.odoo.url')),
            'odoo_database' => Setting::get('odoo_database', config('services.odoo.database')),
            'odoo_username' => Setting::get('odoo_username', config('services.odoo.username')),
            'odoo_password' => Setting::get('odoo_password', config('services.odoo.password')),
        ];

        // Fetch active fiscal year
        $activeYear = FiscalYear::where('is_active', true)->first();
        
        $departments = [];
        if ($activeYear) {
            $departments = Department::with(['budgetCategories' => function($q) use ($activeYear) {
                $q->where('fiscal_year_id', $activeYear->id)->orderBy('name');
            }])->where('is_active', true)->get();
        }

        return view('fat.settings.index', compact('settings', 'departments', 'activeYear'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'app_favicon' => 'nullable|image|mimes:ico,png,svg|max:1024',
            'procurement_api_key' => 'nullable|string|max:255',
            'odoo_url' => 'nullable|url|max:255',
            'odoo_database' => 'nullable|string|max:255',
            'odoo_username' => 'nullable|string|max:255',
            'odoo_password' => 'nullable|string|max:255',
            'active_categories' => 'nullable|array',
            'active_categories.*' => 'integer',
        ]);

        Setting::set('app_name', $request->app_name);
        Setting::set('procurement_api_key', $request->procurement_api_key);
        Setting::set('odoo_url', $request->odoo_url);
        Setting::set('odoo_database', $request->odoo_database);
        Setting::set('odoo_username', $request->odoo_username);
        Setting::set('odoo_password', $request->odoo_password);

        if ($request->hasFile('app_logo')) {
            $oldLogo = Setting::get('app_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $logoPath = $request->file('app_logo')->store('settings', 'public');
            Setting::set('app_logo', $logoPath);
        }

        if ($request->hasFile('app_favicon')) {
            $oldFavicon = Setting::get('app_favicon');
            if ($oldFavicon) {
                Storage::disk('public')->delete($oldFavicon);
            }
            $faviconPath = $request->file('app_favicon')->store('settings', 'public');
            Setting::set('app_favicon', $faviconPath);
        }

        // Update active categories
        $activeYear = FiscalYear::where('is_active', true)->first();
        if ($activeYear) {
            $activeCategoryIds = $request->input('active_categories', []);
            
            // Set all categories for this fiscal year as inactive first
            BudgetCategory::where('fiscal_year_id', $activeYear->id)->update(['is_active' => false]);
            
            if (!empty($activeCategoryIds)) {
                BudgetCategory::where('fiscal_year_id', $activeYear->id)
                    ->whereIn('id', $activeCategoryIds)
                    ->update(['is_active' => true]);
            }
        }

        return redirect()->route('fat.settings.index')->with('success', 'Pengaturan aplikasi berhasil disimpan.');
    }

    public function testOdooConnection(Request $request)
    {
        $request->validate([
            'odoo_url' => 'required|url|max:255',
            'odoo_database' => 'required|string|max:255',
            'odoo_username' => 'required|string|max:255',
            'odoo_password' => 'required|string|max:255',
        ]);

        $syncService = app(\App\Services\OdooSyncService::class);
        $result = $syncService->testConnection(
            $request->odoo_url,
            $request->odoo_database,
            $request->odoo_username,
            $request->odoo_password
        );

        return response()->json($result);
    }
}
