<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
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
        $user = DB::transaction(function () use ($data) {
            $user = User::create(Arr::except($data, 'role_ids'));
            $user->roles()->sync($data['role_ids'] ?? []);

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
        DB::transaction(function () use ($data, $user) {
            $user->update(Arr::except($data, ['role_ids', 'password']));
            if (filled($data['password'] ?? null)) {
                $user->update(['password' => $data['password']]);
            }
            $user->roles()->sync($data['role_ids'] ?? []);
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

    private function record(string $event, User $subject): void
    {
        DB::table('activity_logs')->insert(['user_id' => auth()->id(), 'event' => $event, 'subject_type' => User::class, 'subject_id' => $subject->id, 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'properties' => null, 'created_at' => now()]);
    }
}
