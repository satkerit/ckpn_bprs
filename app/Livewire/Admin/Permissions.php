<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

#[Layout('layouts.app')]
class Permissions extends Component
{
    public string $name = '';

    public ?int $editingId = null;

    public function edit(int $id): void
    {
        Gate::authorize('permission.manage');

        $permission = Permission::query()->findOrFail($id);
        $this->editingId = $permission->id;
        $this->name = $permission->name;
    }

    public function batalEdit(): void
    {
        $this->reset(['editingId', 'name']);
    }

    public function simpan(): void
    {
        Gate::authorize('permission.manage');

        $data = $this->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('permissions', 'name')->ignore($this->editingId),
            ],
        ]);

        if ($this->editingId === null) {
            $permission = Permission::query()->create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);
            $action = 'permission.created';
            $message = 'Permission berhasil dibuat.';
        } else {
            $permission = Permission::query()->findOrFail($this->editingId);
            $permission->update(['name' => $data['name']]);
            $action = 'permission.updated';
            $message = 'Permission berhasil diperbarui.';
        }

        AuditLog::catat($action, $permission);
        $this->reset(['editingId', 'name']);
        session()->flash('status', $message);
    }

    public function hapus(int $id): void
    {
        Gate::authorize('permission.manage');

        $permission = Permission::query()->findOrFail($id);
        abort_if($permission->name === 'permission.manage', 422, 'Permission utama tidak dapat dihapus.');

        AuditLog::catat('permission.deleted', $permission);
        $permission->delete();
    }

    public function render(): View
    {
        Gate::authorize('permission.manage');

        return view('livewire.admin.permissions', [
            'permissions' => Permission::query()->withCount('roles')->orderBy('name')->get(),
        ]);
    }
}
