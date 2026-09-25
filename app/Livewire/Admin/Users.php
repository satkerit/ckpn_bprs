<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class Users extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    public function simpan(): void
    {
        Gate::authorize('user.manage');
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
        ]);
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $user->assignRole($data['role']);
        AuditLog::catat('user.created', $user, ['role' => $data['role']]);
        $this->reset(['name', 'email', 'password', 'role']);
        session()->flash('status', 'User berhasil dibuat.');
    }

    public function hapus(int $id): void
    {
        Gate::authorize('user.manage');
        abort_if($id === auth()->id(), 422, 'User yang sedang login tidak dapat dihapus.');
        $user = User::query()->findOrFail($id);
        AuditLog::catat('user.deleted', $user);
        $user->delete();
    }

    public function render(): View
    {
        Gate::authorize('user.manage');

        return view('livewire.admin.users', [
            'users' => User::query()->with('roles')->latest()->get(),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }
}
