<?php

namespace App\Services;

use App\Models\User;
use App\Models\Module;
use App\Models\Country;
use App\Models\Designation;
use App\Models\Role;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Jobs\SendEmailJob;

class ProfileSettingService extends BaseService
{
    protected $userService;

    public function __construct(User $model, UserService $userService)
    {
        parent::__construct($model);
        $this->userService = $userService;
    }

    public function getPageData()
    {
        return (object)[
            'singular_name' => __('profile-settings/change-password.profile_setting'),
            'plural_name' => __('profile-settings/change-password.profile_settings')
        ];
    }

    public function getUserDetails()
    {
        $user = auth()->user();
        $user->profile_image_component = $this->userService->getProfileImageComponent([
            'imageValue' => $user->profile_photo_url,
            'id' => 'profile_photo',
            'inputName' => 'profile_photo',
            'isEditable' => true,
        ]);

        $designations = Designation::select('title', 'short_title', 'id')->orderBy('sort_order', 'asc')->get();
        $roles = Role::select('title', 'id')->get();

        $countries = Country::select('name', 'id')->get();

        // Get additional info data
        $additionalInfo = $user->additionalInfo;
        $states = [];
        $cities = [];

        if ($additionalInfo && $additionalInfo->country_id) {
            $states = $this->userService->getStates($additionalInfo->country_id);
            if ($additionalInfo->state_id) {
                $cities = $this->userService->getCities($additionalInfo->state_id);
            }
        }

        return [
            'user' => $user,
            'designations' => $designations,
            'roles' => $roles,
            'countries' => $countries,
            'states' => $states,
            'cities' => $cities,
            'additionalInfo' => $additionalInfo
        ];
    }

    public function updateProfile(array $data)
    {
        $user = auth()->user();

        // Use UserService's update functionality
        $data['id'] = $user->id;
        $result = $this->userService->updateItem($data);

        if ($result) {
            $user->refresh();

            $user->profile_image_component = $this->userService->getProfileImageComponent([
                'imageValue' => $user->profile_photo_url,
                'id' => 'profile_photo',
                'inputName' => 'profile_photo',
                'isEditable' => true,
            ]);
        }

        return $user;
    }

    public function removeProfilePhoto()
    {
        $user = auth()->user();

        // Delete the existing photo
        if ($user->profile_photo) {
            Storage::delete($user->profile_photo);
        }

        // Update user record
        $user->update(['profile_photo' => null]);

        $user->profile_image_component = $this->userService->getProfileImageComponent([
            'imageValue' => $user->profile_photo_url,
            'id' => 'profile_photo',
            'inputName' => 'profile_photo',
            'isEditable' => true,
        ]);

        $data = [
            'profile_image_component' => $user->profile_image_component,
        ];

        return $data;
    }

    public function updatePassword(array $data)
    {
        $user = auth()->user();

        // Validate current password and update
        if (!Hash::check($data['current_password'], $user->password)) {
            throw new \Exception('Current password is incorrect');
        }

        return $user->update([
            'password' => Hash::make($data['new_password'])
        ]);
    }

    public function updateAdditionalInfo(array $data)
    {
        $data['user_id'] = auth()->id();
        return $this->userService->updateAdditionalInfo($data);
    }
}
