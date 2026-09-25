<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-surface-900">Audit Log</h2>
        <p class="mt-1 text-sm text-surface-500">Aktivitas perubahan sistem, terbaru di atas.</p>
    </div>

    <div class="card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Waktu</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">User</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Aksi</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">Model</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-surface-500">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-200 bg-white">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-surface-50">
                            <td class="whitespace-nowrap px-5 py-3 text-surface-500">{{ $log->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="px-5 py-3 text-surface-600">{{ $log->user?->email ?? '-' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-700">{{ $log->aksi }}</span>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-surface-600">{{ $log->model ?? '-' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-surface-500">{{ $log->ip ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-surface-500">Belum ada aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
