<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index', ['roles' => Role::withCount('users')->orderBy('display_name')->get()]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.form', ['role' => new Role, 'permissions' => Permission::orderBy('name')->get()]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $permissionIds = $this->grantablePermissionIds($request, $data['permission_ids'] ?? []);

        $role = DB::transaction(function () use ($data, $permissionIds) {
            $role = Role::create(Arr::except($data, 'permission_ids'));
            $role->permissions()->sync($permissionIds);

            return $role;
        });
        $this->record('roles.created', $role);

        return redirect()->route('admin.roles.index')->with('status', 'تم إنشاء الدور.');
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('admin.roles.form', ['role' => $role->load('permissions'), 'permissions' => Permission::orderBy('name')->get()]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();
        $permissionIds = $this->grantablePermissionIds($request, $data['permission_ids'] ?? [], $role);

        DB::transaction(function () use ($data, $role, $permissionIds) {
            $role->update(Arr::except($data, 'permission_ids'));
            $role->permissions()->sync($permissionIds);
        });
        $this->record('roles.updated', $role);

        return redirect()->route('admin.roles.index')->with('status', 'تم تحديث الدور وصلاحياته.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);
        $this->record('roles.deleted', $role);
        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'تم حذف الدور.');
    }

    /**
     * A role manager must never be able to grant a permission they don't
     * themselves hold — otherwise "roles.create" + "roles.update" alone
     * would let someone build a fresh role with every permission (i.e. a
     * super-admin in every way that matters) and hand it to another
     * account. Permissions outside the actor's own set are left untouched
     * on the existing role rather than stripped, so opening the edit form
     * and saving can't silently downgrade a role's privileges they can't see.
     */
    private function grantablePermissionIds(Request $request, array $requestedIds, ?Role $existingRole = null): array
    {
        $actor = $request->user();
        if ($actor->hasRole('super-admin')) {
            return $requestedIds;
        }

        $actorPermissionIds = $actor->roles()->with('permissions')->get()
            ->pluck('permissions')->flatten()->pluck('id')->unique();

        $granted = array_intersect($requestedIds, $actorPermissionIds->all());
        $preserved = $existingRole
            ? $existingRole->permissions()->pluck('permissions.id')->diff($actorPermissionIds)->all()
            : [];

        return array_values(array_unique([...$granted, ...$preserved]));
    }

    private function record(string $event, Role $subject): void
    {
        ActivityLogger::log($event, $subject, ['name' => $subject->name, 'permissions' => $subject->permissions->pluck('name')->all()]);
    }
}
