<?php

namespace App\Http\Controllers\Auth;

use Carbon\Carbon;
use App\Models\City;
use App\Models\User;
use App\Models\State;
use App\Models\Country;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Events\BaseEmailEvent;
use App\Mail\ResetPasswordMail;
use App\Events\ResetPasswordEvent;
use App\Http\Controllers\Controller;
use App\Models\ResetPasswordHistory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function index(Request $request)
    {
        try {
            $token = $request->token;

            $decryptedToken = Crypt::decryptString($token);
            list($randomString, $userId, $timestamp) = explode('|', $decryptedToken);

            $user = User::findOrFail($userId);
            if ($user->set_password_token !== $token) {
                return redirect()->route('unauthorized');
            }

            return view('auth.passwords.reset', ['request' => $request]);
        } catch (\Exception $e) {
            // session()->flash('error', 'Invalid token or user not found');
            return redirect()->route('unauthorized');
            // dd($e->getMessage());
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $data = $request->validate([
                'token' => 'required',
                'password' => 'required|confirmed',
            ]);

            $token = $data['token'];
            $newPassword = $data['password'];


            $decryptedToken = Crypt::decryptString($token);
            list($randomString, $userId, $timestamp) = explode('|', $decryptedToken);

            $user = User::findOrFail($userId);

            if ($user->set_password_token !== $token) {
                session()->flash('error', 'Invalid token');
                return;
            }

            // if (Carbon::createFromTimestamp($timestamp)->addHours(1)->isPast()) {
            //     $this->markTokenAsExpired($user);
            //     session()->flash('error', 'Token expired');
            //     return;
            // }

            $user->update([
                'password' => bcrypt($newPassword),
                'set_password_token' => null,
            ]);

            ResetPasswordHistory::where('user_id', $userId)
                ->latest()
                ->first()
                ->update(['is_visited' => 1]);

            session()->flash('success', 'Password reset successfully!');

            // logout the already loggedin user and login this user
            auth('web')->logout();
            auth('web')->login($user);

            return redirect()->route('home');

            // return redirect()->route('login');
        } catch (ValidationException $e) {
            // If validation fails, return to the previous page with errors
            return redirect()->back()->withInput()->withErrors($e->validator->errors());
        } catch (\Exception $e) {
            // session()->flash('error', 'Invalid token or user not found');
            \Log::error($e->getMessage());
            return redirect()->route('unauthorized');
        }
    }
    protected function markTokenAsExpired($user)
    {
        $user->update(['set_password_token' => null]);

        ResetPasswordHistory::where('user_id', $user->id)
            ->latest()
            ->first()
            ->update(['is_visited' => 2]);
    }


    public function setPasswordRequest(Request $request)
    {
        try {
            $token = $request->token;
            $decryptedToken = Crypt::decryptString($token);
            list($randomString, $userId, $timestamp) = explode('|', $decryptedToken);

            $user = User::findOrFail($userId);
            $user->load('additionalInfo');

            if ($user->set_password_token !== $token) {
                return redirect()->route('unauthorized');
            }

            $countries = Country::all();
            return view('auth.passwords.set-password', ['request' => $request, 'user' => $user, 'countries' => $countries]);
        } catch (\Exception $e) {
            // session()->flash('error', 'Invalid token or user not found');
            return redirect()->route('unauthorized');
        }
    }

    public function getStates(Request $request)
    {
        $states = State::where('country_id', $request->country_id)->get();
        return response()->json($states);
    }

    public function getCities(Request $request)
    {
        $cities = City::where('state_id', $request->state_id)->get();
        return response()->json($cities);
    }

    public function setPassword(Request $request)
    {
        try {
            $skipValidation = $request->input('skip', false); // This can be sent as a hidden input or similar

            $rules = [
                'first_name' => 'required|max:255',
                'middle_name' => 'nullable|max:255',
                'last_name' => 'required|max:255',
                'name' => 'required|max:255',
                'phone' => 'nullable|max:255',
                'gender' => 'required|in:1,2,3',
                'dob' => 'required|date',
                'country_id' => 'required',
                'state_id' => 'nullable',
                'city_id' => 'nullable  ',
                'street_address' => 'required|max:255',
                'post_code' => 'required|numeric',
                'emerg_contact_name' => 'nullable|max:255',
                'emerg_contact_phone' => 'nullable|numeric',
                'emerg_contact_relation' => 'nullable|string|max:255',
                'token' => 'required',
                'password' => 'required|confirmed',
            ];
            // Modify rules based on skip validation flag
            if ($skipValidation == 'true') {
                // Remove fields from validation rules that can be skipped
                unset($rules['country_id']);
                unset($rules['state_id']);
                unset($rules['city_id']);
                unset($rules['street_address']);
                unset($rules['post_code']);
            }

            // Validate the request data
            $data = $request->validate($rules);
            $token = $data['token'];

            $decryptedToken = Crypt::decryptString($token);
            list($randomString, $userId, $timestamp) = explode('|', $decryptedToken);

            $user = User::findOrFail($userId);

            if ($user->set_password_token !== $token) {
                session()->flash('error', 'Your has been expired!');
                return;
            }

            // if (Carbon::createFromTimestamp($timestamp)->addHours(1)->isPast()) {
            //     $this->markTokenAsExpired($user);
            //     session()->flash('error', 'Token expired');
            //     return;
            // }

            $data['password'] = bcrypt($data['password']);
            $data['set_password_token'] = null;

            $user->update($data);
            $user->additionalInfo()->updateOrCreate(['user_id' => $user->id], $data);

            session()->flash('success', 'Your account has been updated successfully!');

            auth('web')->logout();
            auth('web')->login($user);

            return redirect()->route('home');
        } catch (ValidationException $e) {
            // If validation fails, return to the previous page with errors
            return redirect()->back()->withInput()->withErrors($e->validator->errors());
        } catch (\Exception $e) {
            // session()->flash('error', 'Invalid token or user not found');
            return redirect()->route('unauthorized');
        }
    }
}
