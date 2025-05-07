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
            'erm' => ['Dokter', 'Perawat', 'Pendaftaran'],
            'hrd' => ['Hrd'],
            'inventory' => ['Inventaris'],
            'marketing' => ['Marketing'],
        ];

        // Check if the module is valid
        if (!isset($roleMapping[$module])) {
            return back()->withErrors(['email' => 'Invalid login module.'])->withInput();
        }

        // Find user with given email
        $user = User::where('email', $request->email)->first();

        // Check if user exists and has the correct role
        if (!$user || !in_array($user->getRoleNames()->first(), $roleMapping[$module])) {
            return back()->withErrors(['email' => 'Email or password is incorrect.'])->withInput();
        }

        // Attempt to login
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Redirect to the correct dashboard
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
