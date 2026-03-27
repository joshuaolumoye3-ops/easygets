@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<!-- Main Content -->
<main class="pt-[70px] w-full flex flex-col">
    <div class="p-[1.875rem_1.75rem] w-full flex-grow">
        
        <!-- Top Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center bg-[#191c24] p-4 rounded-2xl shadow-md mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-white mb-4 md:mb-0 text-center md:text-left">
                Admin List
            </h1>
            <div class="flex items-center space-x-3 sm:space-x-4 w-full md:w-auto">
                {{-- <input 
                    id="userSearch"
                    type="text" 
                    placeholder="Search users..." 
                    class="flex-1 md:flex-none px-3 sm:px-4 py-2 w-full sm:w-auto rounded-lg bg-input_bg border border-border_color text-white focus:outline-none focus:ring-2 focus:ring-modern-primary text-sm sm:text-base"
                > --}}
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-modern-primary flex items-center justify-center text-white font-bold text-sm sm:text-base">
                    A
                </div>
            </div>
        </div>
                <!-- page breadcrumb end  -->
                <div class="flex flex-row w-full">
                    <div class="grid grid-cols-1 sm-max:grid-cols-1 gap-6 mb-[1.5rem] w-full">
                        
                        <div class="rounded-md bg-[#191c24] col-span-1">
                            <div class="py-[1.88rem] px-[1.81rem] text-white">
                                <h4 class="font-bold mb-4">Admins List</h4>
                                <div class="overflow-auto">
                                    <table class="w-full table-auto">
                                        <thead>
                                            <tr class="border-b border-muted/20 text-muted text-sm">
                                                <th class="text-start w-3/12 p-[0.9375rem]">Name</th>
                                                <th class="text-start w-3/12 p-[0.9375rem]">Email</th>
                                                <th class="text-start w-2/12 p-[0.9375rem]">Role</th>
                                                <th class="text-start w-2/12 p-[0.9375rem]">Phone</th>
                                                <th class="text-start w-2/12 p-[0.9375rem]">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($admins as $admin)
                                                <tr 
                                                    class="border-b border-muted/20 hover:bg-dark cursor-pointer"
                                                    onclick="openAdminModal({{ $admin->id }})"
                                                >
                                                    <td class="p-[0.9375rem]">{{ $admin->name }}</td>
                                                    <td class="p-[0.9375rem]">{{ $admin->email }}</td>
                                                    <td class="p-[0.9375rem]">{{ ucfirst($admin->role) }}</td>
                                                    <td class="p-[0.9375rem]">{{ $admin->phone ?? 'N/A' }}</td>
                                                    <td class="p-[0.9375rem]">
                                                        @if($admin->status === 'active')
                                                            <span class="inline-flex items-center rounded-md bg-modern-success px-2 py-1 text-xs font-medium text-gray-600">
                                                                Active
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center rounded-md bg-modern-danger px-2 py-1 text-xs font-medium text-gray-600">
                                                                Inactive
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center p-4 text-muted">No admins found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div class="mt-4">
                                    {{ $admins->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<!-- Admin Details Modal -->
<div id="adminModal" class="fixed inset-0 hidden flex items-center justify-center z-50" 
     style="background: rgba(0,0,0,0.7);">
    <div style="background:#121417; color:white; border:1px solid #16a34a; border-radius:8px; width:100%; max-width:700px; max-height:90vh; padding:24px; position:relative; overflow-y:auto; box-shadow:0 0 20px rgba(0,0,0,0.6);">
        
        <!-- Close Button -->
        <button onclick="closeAdminModal()" 
            style="position:absolute; top:10px; right:14px; color:#9ca3af; font-size:22px; font-weight:bold; cursor:pointer;"
            onmouseover="this.style.color='#22c55e'" 
            onmouseout="this.style.color='#9ca3af'">
            &times;
        </button>
        
        <!-- Modal Title -->
        <h2 style="font-size:20px; font-weight:bold; margin-bottom:16px; color:#22c55e; border-bottom:1px solid #14532d; padding-bottom:8px;">
            Admin Details
        </h2>

        <!-- Admin Info -->
        <div id="adminDetails" style="margin-bottom:20px; padding:12px; border:1px solid #374151; border-radius:6px; background:#1a1d22;"></div>

        <!-- Transactions -->
        <h3 class="font-semibold mb-3 text-green-400 text-lg">Transactions</h3>
        <div id="transactions" 
            class="max-h-64 overflow-y-auto border border-green-800 rounded-lg p-3 bg-[#1a1d22] space-y-3 scrollbar-thin scrollbar-thumb-green-700 scrollbar-track-transparent">
        </div>

        <!-- Update Form -->
        <h3 style="font-weight:600; margin-top:20px; margin-bottom:12px; color:#4ade80;">Update Admin</h3>
        <form id="updateAdminForm" method="POST" style="display:flex; flex-direction:column; gap:12px;">
            @csrf
            <div>
                <label style="display:block; font-size:14px; margin-bottom:4px;">Role</label>
                <select name="role" id="adminRole" 
                        style="width:100%; padding:8px; border:1px solid #15803d; border-radius:6px; background:#1a1d22; color:white;">
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            <div>
                <label style="display:block; font-size:14px; margin-bottom:4px;">New Password</label>
                <input type="password" name="password" 
                       style="width:100%; padding:8px; border:1px solid #15803d; border-radius:6px; background:#1a1d22; color:white;">
            </div>
            <div>
                <label style="display:block; font-size:14px; margin-bottom:4px;">Confirm Password</label>
                <input type="password" name="password_confirmation" 
                       style="width:100%; padding:8px; border:1px solid #15803d; border-radius:6px; background:#1a1d22; color:white;">
            </div>
            <button type="submit" 
                    style="margin-top:12px; background:#16a34a; border:none; padding:10px 20px; border-radius:6px; color:white; font-weight:600; cursor:pointer; transition:0.3s;"
                    onmouseover="this.style.background='#15803d'" 
                    onmouseout="this.style.background='#16a34a'">
                Update
            </button>
        </form>
    </div>
</div>

<script>
function openAdminModal(adminId) {
    const baseUrl = '{{ url('/') }}';
    fetch(`${baseUrl}/app/admin/${adminId}`)
        .then(res => res.json())
        .then(admin => {
            // Fill Admin Details
            document.getElementById('adminDetails').innerHTML = `
                <p><strong>Name:</strong> ${admin.name}</p>
                <p><strong>Email:</strong> ${admin.email}</p>
                <p><strong>Role:</strong> ${admin.role}</p>
                <p><strong>Phone:</strong> ${admin.phone ?? 'N/A'}</p>
            `;

            // Fill Transactions
            let txHtml = '';
            if (admin.transactions.length > 0) {
                txHtml = admin.transactions.map(tx => {
                    const statusColor = tx.status === 'success' 
                        ? 'text-green-400 border-green-800 bg-green-900/20' 
                        : tx.status === 'failed'
                        ? 'text-red-400 border-red-800 bg-red-900/20'
                        : 'text-yellow-400 border-yellow-800 bg-yellow-900/20';

                    return `
                    <div class="border ${statusColor} rounded-lg p-3 text-sm flex flex-col gap-1">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-white capitalize">${tx.type ?? 'Transaction'}</span>
                            <span class="text-xs ${statusColor.split(' ')[0]} uppercase font-medium">${tx.status}</span>
                        </div>
                        <div class="flex justify-between text-gray-400 text-xs">
                            
                            
                        </div>
                        <div class="text-gray-300 mt-1">
                            ₦${Number(tx.amount).toLocaleString()} 
                            <span class="text-gray-500 text-xs">| Ref: ${tx.reference}</span>
                        </div>
                        <div class="text-gray-500 text-xs mt-1">${tx.created_at ?? ''}</div>
                    </div>`;
                }).join('');
            } else {
                txHtml = `<p class="text-gray-400 text-sm text-center">No transactions found</p>`;
            }
            document.getElementById('transactions').innerHTML = txHtml;

            // Update Form Action
            const baseUrl = '{{ url('/') }}';
            document.getElementById('updateAdminForm').action = `${baseUrl}/app/admin/${admin.id}/update`;

            // Show Modal
            document.getElementById('adminModal').classList.remove('hidden');
        });
}

function closeAdminModal() {
    document.getElementById('adminModal').classList.add('hidden');
}
</script>

<style>
    /* Scrollbar Styling for Chrome, Edge, etc. */
    #transactions::-webkit-scrollbar {
        width: 6px;
    }
    #transactions::-webkit-scrollbar-thumb {
        background-color: #15803d;
        border-radius: 8px;
    }
    #transactions::-webkit-scrollbar-track {
        background: transparent;
    }
</style>
@endsection