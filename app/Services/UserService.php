<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\City;
use App\Models\Role;
use App\Models\User;
use App\Models\State;
use App\Models\Country;
use App\Models\Designation;
use Illuminate\Support\Str;
use App\Http\Resources\UserDTR;
use App\Models\UserAdditionalInfo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\UserAdditionalInfoDTR;
use App\Http\Resources\UserResetPasswordHistoryDTR;
use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class UserService extends BaseService
{
    function __construct(User $model)
    {
        parent::__construct($model, UserDTR::class);
    }

    public function createItem(array $data)
    {
        // Store invite_user flag and remove it from data array
        $shouldInviteUser = isset($data['invite_user']) && $data['invite_user'] == 1;
        unset($data['invite_user']);

        // Set the full name
        $data['name'] = trim(ucfirst($data['first_name']) . ' ' . ucfirst($data['last_name']));

        // Create the user
        $user = parent::createItem($data);

        // Send invitation if needed
        if ($shouldInviteUser) {
            $this->inviteUser($user->id);
        }

        return $user;
    }

    private function uploadProfilePhoto($data)
    {
        $uniquesavename = time() . uniqid(rand());
        $fileName = $uniquesavename . '-' . $data['profile_photo']->getClientOriginalName();

        $fileUrl = Storage::disk('public')->putFileAs(
            'profile_images',
            $data['profile_photo'],
            $fileName
        );

        return $fileUrl;
    }

    public function updateItem(array $data)
    {
        $data['dob'] = date('Y-m-d', strtotime($data['dob']));

        if (isset($data['profile_photo'])) {
            $data['profile_photo'] = $this->uploadProfilePhoto($data);
        }

        $user = parent::updateItem($data);
        $user->refresh();
        $user->profile_image_component = $this->getProfileImageComponent([
            'imageValue' => $user->profile_photo_url,
            'id' => 'profile_photo',
            'inputName' => 'profile_photo',
            'isEditable' => true,
        ]);
        return $user;
    }

    public function getParents()
    {
        $data['designations'] = Designation::select('title', 'short_title', 'id')->orderBy('sort_order', 'asc')->get();
        $data['roles'] = Role::select('title', 'id')->orderBy('title', 'asc')->get();
        return $data;
    }

    public function getItems($request, $archived = false)
    {
        $query = $archived ? $this->model::onlyTrashed() : $this->model::query();

        $loggedInUserId = auth()->id();
        $query->where('users.id', '!=', $loggedInUserId);

        $this->addJoins($query);

        $column_index = $request->order[0]['column'];
        $columnName = $request->columns[$column_index]['data'];
        $columnName = $this->mapColumnName($columnName);
        $column_sort_order = $request->order[0]['dir'];
        $search_value = $request->search['value'];

        $searchColumns = $this->getDefaultSearchColumns(); // Get columns dynamically

        if (!empty($search_value)) {
            $query->where(function ($q) use ($search_value, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'like', '%' . $search_value . '%');
                }
            });
        }

        $cacheKey = 'records_total_' . ($archived ? 'archived' : 'active');

        $records_total = cache()->remember($cacheKey, 600, function () use ($archived, $loggedInUserId) {
            return $archived ? $this->model::onlyTrashed()->where('users.id', '!=', $loggedInUserId)->count() : $this->model::where('users.id', '!=', $loggedInUserId)->count();
        });

        $records_filtered = $query->count();

        $users = $query->orderBy($columnName, $column_sort_order)
            ->skip($request->start)
            ->take($request->length)
            ->get();

        return UserDTR::collection($users)->additional([
            "draw" => intval($request->draw),
            "recordsTotal" => $records_total,
            "recordsFiltered" => $records_filtered,
        ]);
    }

    protected function addJoins($query)
    {
        $query->leftJoin('designations', 'users.designation_id', '=', 'designations.id')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->select([
                'users.*',
                'designations.short_title as designation_title',
                'roles.title as role_title',
            ]);
    }

    protected function getDefaultSearchColumns()
    {
        return [
            'users.name',
            'users.email',
            'users.first_name',
            'users.middle_name',
            'users.last_name',
            'users.phone',
            'designations.short_title',
            'roles.title',
        ];
    }

    protected function mapColumnName($columnName)
    {
        $columnMap = [
            'display_name' => 'users.name',
            'full_name' => 'users.first_name',
            'designation' => 'designations.short_title',
            'user_role' => 'roles.title',
        ];

        return $columnMap[$columnName] ?? $columnName; // Return mapped column or original if not found
    }

    public function getSingle($id)
    {
        $user = $this->getById($id);
        $showInviteBtn = ($user->password == null) ? true : false;
        $user->show_invite_btn = $showInviteBtn;

        $profileImageComponent = $this->getProfileImageComponent([
            'imageValue' => $user->profile_photo_url,
            'id' => 'profile_photo_editForm_general',
            'inputName' => 'profile_photo',
            'isEditable' => true,
        ]);

        $user->profile_image_component = $profileImageComponent;

        $designations = Designation::select('title', 'short_title', 'id')->orderBy('sort_order', 'asc')->get();
        $roles = Role::select('title', 'id')->orderBy('title', 'asc')->get();
        $countries = Country::select('name', 'id')->get();

        $additionalInfo = $user->additionalInfo ? new UserAdditionalInfoDTR($user->additionalInfo) : '';

        return [
            'data' => $user,
            'designations' => $designations,
            'roles' => $roles,
            'countries' => $countries,
            'additionalInfo' => $additionalInfo
        ];
    }

    public function getProfileImageComponent(array $params)
    {
        $defaults = [
            'imageValue' => null,
            'id' => 'profile_photo_editForm_general',
            'inputName' => 'profile_photo',
            'isEditable' => false,
            'isHeader' => false,
            'size' => 'avatar-xl'
        ];

        $options = array_merge($defaults, $params);
        return view('components.profile-image', $options)->render();
    }


    public function getStates($countryId)
    {
        return State::select('name', 'id')->where('country_id', $countryId)->get();
    }

    public function getCities($stateId)
    {
        return City::select('name', 'id')->where('state_id', $stateId)->get();
    }

    public function updateAdditionalInfo(array $data)
    {
        return UserAdditionalInfo::updateOrCreate(
            ['user_id' => $data['user_id']],
            [
                'country_id' => $data['country_id'],
                'state_id' => $data['state_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'street_address' => $data['street_address'],
                'post_code' => $data['post_code'],
                'emerg_contact_name' => $data['emerg_contact_name'],
                'emerg_contact_phone' => $data['emerg_contact_phone'],
                'emerg_contact_relation' => $data['emerg_contact_relation'],
                'updated_by' => auth()->id() ?? null
            ]
        );
    }
    public function resetPasswordRequest($userId)
    {
        $user = User::findOrFail($userId);
        $token = $this->generateToken($userId);

        // Update existing reset password requests
        $user->resetPasswordHistories()->where('is_visited', 0)->update(['is_visited' => 2]);

        // Create new reset password history
        $data = [
            'created_by' => auth('web')->user()->id,
            'email' => $user->email,
            'ip' => Request::ip(),
            'is_visited' => 0,
            'agent' => $this->extractOSAndBrowser(Request::header('User-Agent'))
        ];

        $user->resetPasswordHistories()->create($data);
        $user->update(['set_password_token' => $token]);

        // Prepare email data
        $isNewUser = $user->password === null;
        $emailData = [
            'event' => $isNewUser ? 'SetPasswordEvent' : 'ResetPasswordEvent',
            'Name' => $user->name,
            'Email' => $user->email,
            'Logo' => '<img src="' . getGeneralSettings()->logo_dark_lg_path . '" alt="Logo" style="width: 100px; height: auto;">',
        ];

        // Add the appropriate link key based on user type
        $linkKey = $isNewUser ? 'SetPasswordLink' : 'PasswordResetLink';
        $routeName = $isNewUser ? 'user.password.set.request' : 'user.password.reset.request';
        $emailData[$linkKey] = route($routeName, ['token' => $token]);

        // Dispatch email job
        SendEmailJob::dispatch($emailData);
    }

    private function generateToken($userId)
    {
        $randomString = Str::random(10);
        $token = Crypt::encryptString("{$randomString}|{$userId}|" . Carbon::now()->timestamp);
        return $token;
    }

    public function getUserByToken($token)
    {
        $decryptedToken = Crypt::decryptString($token);
        list($randomString, $userId, $timestamp) = explode('|', $decryptedToken);

        if ($userId) {
            return $this->model->find($userId);
        }
        return null;
    }

    private function extractOSAndBrowser($userAgent)
    {
        $os = "Unknown OS";
        $browser = "Unknown Browser";

        // Detect OS
        if (preg_match('/windows/i', $userAgent)) {
            $os = "Windows";
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = "Mac";
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = "Linux";
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = "Android";
        } elseif (preg_match('/iphone/i', $userAgent)) {
            $os = "iOS";
        }

        // Detect Browser
        if (preg_match('/MSIE/i', $userAgent) && !preg_match('/Opera/i', $userAgent)) {
            $browser = "Internet Explorer";
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = "Firefox";
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = "Chrome";
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = "Safari";
        } elseif (preg_match('/Opera/i', $userAgent)) {
            $browser = "Opera";
        } elseif (preg_match('/Netscape/i', $userAgent)) {
            $browser = "Netscape";
        }

        return "$os - $browser";
    }

    public function getResetPasswordHistoryData($request, $userId)
    {
        $user = User::findOrFail($userId);

        $query = $user->resetPasswordHistories()->with('user');

        $query->leftJoin('users', 'reset_password_histories.created_by', '=', 'users.id')
            ->select([
                'reset_password_histories.*',
                'users.name as created_by'
            ]);

        $columnIndex = $request->order[0]['column'];
        $columnName = $request->columns[$columnIndex]['data'];
        $columnName = $this->mapResetPasswordHistoryColumnName($columnName);
        $columnSortOrder = $request->order[0]['dir'];
        $searchValue = $request->search['value'];

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('reset_password_histories.email', 'like', '%' . $searchValue . '%')
                    ->orWhere('reset_password_histories.ip', 'like', '%' . $searchValue . '%')
                    ->orWhere('reset_password_histories.agent', 'like', '%' . $searchValue . '%')
                    ->orWhere('reset_password_histories.created_at', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('user', function ($q) use ($searchValue) {
                        $q->where('name', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        $recordsTotal = $query->count();
        $recordsFiltered = $query->count();

        // Ensure the columnName is valid and exists in the table
        if (!in_array($columnName, ['reset_password_histories.email', 'reset_password_histories.ip', 'reset_password_histories.agent', 'reset_password_histories.created_at', 'users.name', 'reset_password_histories.is_visited'])) {
            $columnName = 'reset_password_histories.created_at'; // Default to a valid column
        }

        $data = $query->orderBy($columnName, $columnSortOrder)
            ->skip($request->start)
            ->take($request->length)
            ->get();

        return UserResetPasswordHistoryDTR::collection($data)->additional([
            "draw" => intval($request->draw),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
        ]);
    }

    private function mapResetPasswordHistoryColumnName($columnName)
    {
        switch ($columnName) {
            case 'user_name':
                return 'users.name';
            case 'created_by':
                return 'users.name';
            case 'email':
                return 'reset_password_histories.email';
            case 'created_at':
                return 'reset_password_histories.created_at';
            default:
                return 'reset_password_histories.' . $columnName;
        }
    }

    public function inviteUser($userId)
    {
        $user = $this->model->findOrFail($userId);

        $isInvitedAlready = ($user->password !== null && $user->set_password_token !== null) ? true : false;

        if ($isInvitedAlready) {
            return;
        }

        // Generate token
        $token = $this->generateToken($userId);

        // Update user with token
        $user->update([
            'set_password_token' => $token
        ]);


        $setPasswordLink = route('user.password.set.request', ['token' => $token]);

        $emailData = [
            'event' => 'SetPasswordEvent',
            'Name' => $user->name,
            'Email' => $user->email,
            'SetPasswordLink' => $setPasswordLink,
            'Logo' => '<img src="' . getGeneralSettings()->logo_dark_lg_path . '" alt="Logo" style="width: 100px; height: auto;">',
        ];

        // Dispatch email job
        SendEmailJob::dispatch($emailData);

        return;
    }

    public function removeProfilePhoto($userId)
    {
        $user = $this->model->findOrFail($userId);

        if ($user->profile_photo) {
            Storage::delete($user->profile_photo);
        }
        $user->update(['profile_photo' => null]);

        $user->profile_image_component = $this->getProfileImageComponent([
            'imageValue' => $user->profile_photo_url,
            'id' => 'profile_photo',
            'inputName' => 'profile_photo',
            'isEditable' => true,
        ]);
        return $user;
    }

    public function updateEmail($data)
    {
        if (isset($data['id'])) {
            $user = $this->getById($data['id']);
        } else {
            $user = auth()->user();
        }

        $email_change_token = $this->generateToken($user->id);

        $user->update(['new_email' => $data['new_email'], 'email_change_token' => $email_change_token]);

        // Send verification email to new email address
        $verificationEmailData = [
            'event' => 'UpdateEmailEvent',
            'Name' => $user->name,
            'Email' => $user->new_email,
            'Logo' => '<img src="' . getGeneralSettings()->logo_dark_lg_path . '" alt="Logo" style="width: 100px; height: auto;">',
            'VerificationLink' => URL::signedRoute(
                'verify.email.change',
                ['token' => $email_change_token]
            )
        ];

        // Send notification email to current email address
        $notificationEmailData = [
            'event' => 'EmailChangeRequestEvent',
            'Name' => $user->name,
            'Email' => $user->email,
            'NewEmail' => $user->new_email,
            'Logo' => '<img src="' . getGeneralSettings()->logo_dark_lg_path . '" alt="Logo" style="width: 100px; height: auto;">'
        ];

        // Dispatch email jobs
        SendEmailJob::dispatch($verificationEmailData);
        SendEmailJob::dispatch($notificationEmailData);

        return [
            'new_email' => $user->new_email,
            'message' => 'Please check your new email address for verification instructions.'
        ];
    }


    public function verifyEmailChange($token)
    {
        $user = $this->getUserByToken($token);

        if (!$user || $user->email_change_token !== $token) {
            return false;
        }

        $user->update(['email' => $user->new_email, 'new_email' => null, 'email_change_token' => null, 'email_verified_at' => now()]);

        auth()->logout();
        return true;
    }
}
