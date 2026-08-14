<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', ['users' => User::with('roles')->latest()->paginate(20)]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.form', ['user' => new User, 'roles' => Role::orderBy('display_name')->get()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roleIds = $this->assignableRoleIds($request, $data['role_ids'] ?? []);

        $user = DB::transaction(function () use ($data, $roleIds) {
            $user = User::create(Arr::except($data, 'role_ids'));
            $user->roles()->sync($roleIds);

            return $user;
        });
        $this->record('users.created', $user);

        return redirect()->route('admin.users.index')->with('status', 'تم إنشاء المستخدم.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.form', ['user' => $user->load('roles'), 'roles' => Role::orderBy('display_name')->get()]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roleIds = $this->assignableRoleIds($request, $data['role_ids'] ?? [], $user);

        DB::transaction(function () use ($data, $user, $roleIds) {
            $user->update(Arr::except($data, ['role_ids', 'password']));
            if (filled($data['password'] ?? null)) {
                $user->update(['password' => $data['password']]);
            }
            $user->roles()->sync($roleIds);
        });
        $this->record('users.updated', $user);

        return redirect()->route('admin.users.index')->with('status', 'تم تحديث المستخدم.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        $this->record('users.deleted', $user);
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'تم حذف المستخدم ويمكن استعادته لاحقًا.');
    }

    /**
     * A user who isn't themselves a super-admin can manage other accounts
     * (given users.create/users.update) but must never be able to grant the
     * super-admin role to anyone — that would be a privilege escalation, so
     * the id is silently dropped from whatever was submitted. Editing an
     * account that already holds the role keeps it, since removing it here
     * would let a lesser-privileged manager demote a super-admin instead.
     */
    private function assignableRoleIds(Request $request, array $roleIds, ?User $target = null): array
    {
        if ($request->user()->hasRole('super-admin')) {
            return $roleIds;
        }

        $superAdminRoleId = Role::where('name', 'super-admin')->value('id');
        if (! $superAdminRoleId) {
            return $roleIds;
        }

        if ($target?->hasRole('super-admin')) {
            return array_unique([...$roleIds, $superAdminRoleId]);
        }

        return array_values(array_diff($roleIds, [$superAdminRoleId]));
    }

    private function record(string $event, User $subject): void
    {
        ActivityLogger::log($event, $subject, ['name' => $subject->name, 'email' => $subject->email]);
    }
}
