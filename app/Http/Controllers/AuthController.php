<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // Show different login forms based on the module
    public function showERMLoginForm()
    {
        return view('auth.erm_login'); // ERM Login Page
    }

    public function showHRDLoginForm()
    {
        return view('auth.hrd_login'); // HRD Login Page
    }

    public function showInventoryLoginForm()
    {
        return view('auth.inventory_login'); // Inventory Login Page
    }

    public function showMarketingLoginForm()
    {
        return view('auth.marketing_login'); // Marketing Login Page
    }
    public function showFinanceLoginForm()
    {
        return view('auth.finance_login'); // Finance Login Page
    }
    public function showWorkdocLoginForm()
    {
        return view('auth.workdoc_login'); // Workdoc Login Page
    }
    public function showAkreditasiLoginForm()
    {
        return view('auth.akreditasi_login'); // Akreditasi Login Page
    }

    // Handle login request
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Get module from the login form
        $module = $request->input('module');

        // Define valid roles for each module
        $roleMapping = [
            'erm' => ['Dokter', 'Perawat', 'Pendaftaran', 'Admin', 'Farmasi','Beautician','Lab'],
            'hrd' => ['Hrd', 'Ceo', 'Manager', 'Employee'],
            'finance' => ['Kasir','Admin'],
            'inventory' => ['Inventaris','Admin'],
            'marketing' => ['Marketing', 'Admin'],
            'workdoc' => ['Hrd', 'Ceo', 'Manager', 'Employee','Admin'],
            'akreditasi' => ['Hrd', 'Ceo', 'Manager', 'Employee','Admin'],
        ];

        // Check if the module is valid
        if (!isset($roleMapping[$module])) {
            return back()->withErrors(['email' => 'Invalid login module.'])->withInput();
        }

        // Find user with given email
        $user = User::where('email', $request->email)->first();

        // Check if user exists and has the correct role
        if (
    !$user ||
    !$user->hasAnyRole($roleMapping[$module])
) {
    return back()->withErrors(['email' => 'Email or password is incorrect.'])->withInput();
}

        // Attempt to login
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Redirect to the correct dashboard or kunjungan rajal page for ERM
            if ($module === 'erm') {
                // Check for Farmasi role
                if ($user->hasRole('Farmasi')) {
                    return redirect()->route('erm.eresepfarmasi.index'); // E-Resep Farmasi menu route
                }
                // Check for Lab role
                if ($user->hasRole('Lab')) {
                    return redirect()->route('erm.elab.index'); // E-Lab index route
                }
                // For Dokter, Pendaftaran, Perawat
                if ($user->hasAnyRole(['Dokter', 'Pendaftaran', 'Perawat'])) {
                    return redirect()->route('erm.rawatjalans.index');
                }
                if ($user->hasRole('Beautician')) {
                    return redirect()->route('erm.spk.index'); // E-SPK index route
                }
                // Default fallback for ERM
                return redirect()->route('erm.dashboard');
            }
            // Redirect to finance billing if module is finance
            if ($module === 'finance') {
                return redirect()->route('finance.billing.index');
            }
            return redirect()->route("$module.dashboard");
        }

        return back()->withErrors(['email' => 'Email or password is incorrect.'])->withInput();
    }

    // Handle logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
