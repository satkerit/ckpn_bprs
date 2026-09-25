<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Audit extends Component
{
    public function render(): View
    {
        Gate::authorize('audit.view');

        return view('livewire.admin.audit', ['logs' => AuditLog::query()->with('user')->latest()->limit(100)->get()]);
    }
}
