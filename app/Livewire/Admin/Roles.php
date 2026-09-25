<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class Roles extends Component
{
    public string $name = '';

    public array $selectedPermissions = [];

    public ?int $editingId = null;

    public function edit(int $id): void
    {
        Gate::authorize('role.manage');

        $role = Role::query()->with('permissions')->findOrFail($id);
        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('id')->map(fn (int|string $id): int => (int) $id)->all();
    }

    public function batalEdit(): void
    {
        $this->reset(['editingId', 'name', 'selectedPermissions']);
    }

    public function simpan(): void
    {
        Gate::authorize('role.manage');
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($this->editingId)],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        if ($this->editingId === null) {
            $role = Role::query()->create(['name' => $data['name'], 'guard_name' => 'web']);
            $action = 'role.created';
            $message = 'Role berhasil dibuat.';
        } else {
            $role = Role::query()->findOrFail($this->editingId);
            abort_if($role->name === 'Administrator' && $data['name'] !== 'Administrator', 422, 'Nama role Administrator tidak dapat diubah.');
            $role->update(['name' => $data['name']]);
            $action = 'role.updated';
            $message = 'Role berhasil diperbarui.';
        }

        $role->syncPermissions($data['selectedPermissions']);
        AuditLog::catat($action, $role, ['permissions' => $data['selectedPermissions']]);
        $this->reset(['editingId', 'name', 'selectedPermissions']);
        session()->flash('status', $message);
    }

    public function hapus(int $id): void
    {
        Gate::authorize('role.manage');
        $role = Role::query()->findOrFail($id);
        abort_if($role->name === 'Administrator', 422, 'Role Administrator tidak dapat dihapus.');
        AuditLog::catat('role.deleted', $role);
        $role->delete();
    }

    public function render(): View
    {
        Gate::authorize('role.manage');

        return view('livewire.admin.roles', [
            'roles' => Role::query()->with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::query()->orderBy('name')->get(),
        ]);
    }
}
