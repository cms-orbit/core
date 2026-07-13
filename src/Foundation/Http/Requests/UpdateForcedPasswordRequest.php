<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateForcedPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user(config('orbit.guard'));

        if ($user === null) {
            return false;
        }

        return method_exists($user, 'shouldChangePassword')
            ? $user->shouldChangePassword()
            : (bool) $user->getAttribute('must_change_password');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }
}
