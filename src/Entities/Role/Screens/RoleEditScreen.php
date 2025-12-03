<?php

namespace CmsOrbit\Core\Entities\Role\Screens;

use CmsOrbit\Core\Entities\Role\Layouts\RoleEditLayout;
use CmsOrbit\Core\Entities\Role\Layouts\RolePermissionLayout;
use CmsOrbit\Core\Auth\Models\Role;
use CmsOrbit\Core\UI\Screen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class RoleEditScreen extends Screen
{
    /**
     * @var Role
     */
    public $role;

    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(Role $role): iterable
    {
        return [
            'role' => $role,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return $this->role->exists ? __('Edit Role') : __('Create Role');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return __('Roles are a way to group permissions together and assign them to users.');
    }

    /**
     * Permission required to access this screen
     */
    public function permission(): ?iterable
    {
        return [
            'orbit.entities.roles',
        ];
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        $user = Auth::user();

        $commands = [];

        if ($user->hasAccess('orbit.entities.roles.remove')) {
            $commands[] = Button::make(__('Remove'))
                ->icon('bs.trash3')
                ->confirm(__('Once the role is deleted, all of its resources and data will be permanently deleted.'))
                ->method('remove')
                ->canSee($this->role->exists);
        }

        return $commands;
    }

    /**
     * The screen's layout elements.
     */
    public function layout(): iterable
    {
        return [
            Layout::block(RoleEditLayout::class)
                ->title(__('Role'))
                ->description(__('A Role defines a set of tasks a user assigned the role is allowed to perform.'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->method('save')
                ),

            Layout::block(RolePermissionLayout::class)
                ->title(__('Permission'))
                ->description(__('Allow the user to perform some actions that are not provided for by his roles'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->method('save')
                ),
        ];
    }

    /**
     * Save role
     */
    public function save(Role $role, Request $request)
    {
        $request->validate([
            'role.slug' => [
                'required',
                Rule::unique(Role::class, 'slug')->ignore($role),
            ],
        ]);

        $permissions = collect($request->get('permissions'))
            ->map(fn ($value, $key) => [base64_decode($key) => $value])
            ->collapse()
            ->toArray();

        $role->fill($request->only(['role.slug', 'role.name'], ['role']))
            ->fill(['permissions' => $permissions])
            ->save();

        Toast::info(__('Role was saved'));

        return redirect()->route('orbit.entities.roles');
    }

    /**
     * Remove role
     */
    public function remove(Role $role)
    {
        $role->delete();

        Toast::info(__('Role was removed'));

        return redirect()->route('orbit.entities.roles');
    }
}
