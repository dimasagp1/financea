@extends('layouts.dashboard', ['title' => 'Manajemen User'])

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Manajemen User & Role</h2>
        <p class="text-slate-500">Khusus SuperAdmin untuk mengelola akses SuperAdmin, FAT, dan Departemen.</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-1">
            <div class="rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/70 border border-slate-100">
                <h3 class="font-semibold mb-4">Tambah User</h3>
                <form method="POST" action="{{ route('fat.users.store') }}" class="space-y-3">
                    @csrf
                    <input type="text" name="name" placeholder="Nama" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required>
                    <input type="email" name="email" placeholder="Email" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required>
                    <input type="password" name="password" placeholder="Password" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required>
                    <select name="role" id="role-select" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required onchange="toggleDeptSelect(this.value, 'manager-depts-section', 'single-dept-section')">
                        <option value="superadmin">SuperAdmin</option>
                        <option value="fat">FAT</option>
                        <option value="manager">Manager (Multi-Dept)</option>
                        <option value="departemen">Departemen (Single-Dept)</option>
                    </select>
                    <div id="manager-depts-section" class="hidden space-y-1">
                        <label class="text-[10px] font-bold text-slate-500 uppercase px-1">Departemen yang Dapat Dilihat</label>
                        <div class="max-h-48 overflow-y-auto rounded-xl border border-slate-300 bg-white p-3 space-y-2 shadow-inner">
                            @foreach($departments as $department)
                                <label class="flex items-center gap-2 cursor-pointer p-1 hover:bg-slate-50 rounded">
                                    <input type="checkbox" name="manager_department_ids[]" value="{{ $department->id }}" class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4 border-slate-300">
                                    <span class="text-sm text-slate-700">{{ $department->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-slate-400 px-1">Pilih satu atau lebih departemen.</p>
                    </div>
                    <div id="single-dept-section" class="block">
                        <select name="department_id" id="single-dept-select" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm">
                            <option value="">- Pilih Department (khusus role departemen) -</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button data-loading-text="Menyimpan user..." class="w-full rounded-xl bg-slate-900 text-white py-2.5">Simpan User</button>
                </form>
            </div>
        </div>

        <div class="xl:col-span-2">
            <div class="rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/70 border border-slate-100 overflow-hidden">
                <h3 class="font-semibold mb-4">Daftar User</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b border-slate-200">
                                <th class="py-3 pr-3">User</th>
                                <th class="py-3 pr-3">Role</th>
                                <th class="py-3 pr-3">Departemen</th>
                                <th class="py-3 pr-3">Status</th>
                                <th class="py-3 pr-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr class="border-b border-slate-100 align-top">
                                    <td class="py-3 pr-3">
                                        <form method="POST" action="{{ route('fat.users.update', $user->id) }}" class="space-y-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="name" value="{{ $user->name }}" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required>
                                            <input type="email" name="email" value="{{ $user->email }}" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required>
                                            <input type="password" name="password" placeholder="Password baru (opsional)" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm">
                                    </td>
                                    <td class="py-3 pr-3">
                                            <select name="role" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required onchange="toggleDeptSelect(this.value, 'manager-depts-edit-{{ $user->id }}', 'single-dept-edit-{{ $user->id }}')">
                                                 <option value="superadmin" @selected($user->role === 'superadmin')>SuperAdmin</option>
                                                 <option value="fat" @selected($user->role === 'fat')>FAT</option>
                                                 <option value="manager" @selected($user->role === 'manager')>Manager</option>
                                                 <option value="departemen" @selected($user->role === 'departemen')>Departemen</option>
                                             </select>
                                     </td>
                                     <td class="py-3 pr-3">
                                             <div id="manager-depts-edit-{{ $user->id }}" class="{{ $user->role === 'manager' ? '' : 'hidden' }} mb-2">
                                                 <label class="text-[9px] font-bold text-slate-400 uppercase">Akses Departemen</label>
                                                 <div class="max-h-32 overflow-y-auto rounded-lg border border-slate-300 bg-white p-2 space-y-1 mt-1 shadow-inner">
                                                     @foreach($departments as $department)
                                                         <label class="flex items-center gap-1.5 cursor-pointer p-1 hover:bg-slate-50 rounded transition-colors">
                                                             <input type="checkbox" name="manager_department_ids[]" value="{{ $department->id }}" class="rounded text-blue-600 focus:ring-blue-500 w-3.5 h-3.5 border-slate-300" @checked($user->managedDepartments->contains($department->id))>
                                                             <span class="text-[11px] text-slate-700 leading-tight">{{ $department->name }}</span>
                                                         </label>
                                                     @endforeach
                                                 </div>
                                             </div>
                                             <div id="single-dept-edit-{{ $user->id }}" class="{{ $user->role === 'departemen' ? '' : 'hidden' }}">
                                                 <select name="department_id" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm">
                                                     <option value="">-</option>
                                                     @foreach($departments as $department)
                                                         <option value="{{ $department->id }}" @selected($user->department_id === $department->id)>{{ $department->name }}</option>
                                                     @endforeach
                                                 </select>
                                             </div>
                                     </td>
                                     <td class="py-3 pr-3">
                                             <select name="is_active" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm">
                                                 <option value="1" @selected($user->is_active)>Aktif</option>
                                                 <option value="0" @selected(!$user->is_active)>Nonaktif</option>
                                             </select>
                                    </td>
                                    <td class="py-3 pr-3">
                                            <div class="flex gap-2">
                                                <button data-loading-text="Mengupdate user..." class="rounded-lg px-3 py-1.5 bg-indigo-600 text-white">Update</button>
                                        </form>
                                        <form method="POST" action="{{ route('fat.users.destroy', $user->id) }}" data-confirm="Hapus user ini?">
                                            @csrf
                                            @method('DELETE')
                                            <button data-loading-text="Menghapus user..." class="rounded-lg px-3 py-1.5 bg-rose-100 text-rose-700">Hapus</button>
                                        </form>
                                            </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-5 text-center text-slate-500">Belum ada user.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $users->links() }}</div>
            </div>
        </div>
    </div>
    <script>
        function toggleDeptSelect(role, multiSectionId, singleSectionId) {
            const multiSection = document.getElementById(multiSectionId);
            const singleSection = document.getElementById(singleSectionId);
            
            if (role === 'manager') {
                multiSection.classList.remove('hidden');
                singleSection.classList.add('hidden');
            } else if (role === 'departemen') {
                multiSection.classList.add('hidden');
                singleSection.classList.remove('hidden');
            } else {
                multiSection.classList.add('hidden');
                singleSection.classList.add('hidden');
            }
        }
    </script>
@endsection
