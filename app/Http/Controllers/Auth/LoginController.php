<?php

namespace App\Http\Controllers\Auth;

use App\Models\Module;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->is_super_admin) {
            return redirect()->intended($this->redirectPath());
        }

        $firstAccessibleModule = Module::whereHas('rolePermissions', function ($query) use ($user) {
            $query->where('role_id', $user->role_id)->where('view', true);
        })->first();

        if ($firstAccessibleModule) {
            return redirect($firstAccessibleModule->url);
        }

        return redirect()->route('unauthorized');
    }
}
