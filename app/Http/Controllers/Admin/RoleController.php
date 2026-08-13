<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
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
        return view('admin.roles.form', ['role' => new Role(), 'permissions' => Permission::orderBy('name')->get()]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = DB::transaction(function () use ($data) { $role = Role::create(Arr::except($data, 'permission_ids')); $role->permissions()->sync($data['permission_ids'] ?? []); return $role; });
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
        DB::transaction(function () use ($data, $role) { $role->update(Arr::except($data, 'permission_ids')); $role->permissions()->sync($data['permission_ids'] ?? []); });
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

    private function record(string $event, Role $subject): void
    {
        DB::table('activity_logs')->insert(['user_id' => auth()->id(), 'event' => $event, 'subject_type' => Role::class, 'subject_id' => $subject->id, 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'properties' => null, 'created_at' => now()]);
    }
}
