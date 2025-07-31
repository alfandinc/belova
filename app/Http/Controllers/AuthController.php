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

        // Find user with given email
        $user = User::where('email', $request->email)->first();

        // Check if user exists
        if (!$user) {
            return back()->withErrors(['email' => 'Email or password is incorrect.'])->withInput();
        }

        // Attempt to login
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Redirect ke main menu
            return redirect('/');
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
