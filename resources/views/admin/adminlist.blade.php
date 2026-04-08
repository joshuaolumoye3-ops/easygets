@extends('layouts.admin')

@section('title', 'Administrator Management')

@section('content')
<!-- Main Content -->
<main class="pt-[70px] w-full flex flex-col min-h-screen bg-gray-50">
    <div class="p-[1.875rem_1.75rem] w-full flex-grow">
        
        <!-- Top Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
            <div class="mb-4 md:mb-0">
                <h1 class="text-2xl font-bold text-gray-900 mb-1">Administrator Management</h1>
                <p class="text-gray-600 text-sm">Manage platform administrators and their permissions</p>
            </div>
            <div class="flex items-center space-x-4 w-full md:w-auto">
                <div class="relative flex-1 md:flex-none md:w-80">
                    <input 
                        type="text" 
                        placeholder="Search administrators..." 
                        class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-colors"
                    >
                    <svg class="absolute left-3 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                    A
                </div>
            </div>
        </div>

        <!-- Admins List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">All Administrators</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $admins->count() }} total
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Administrator</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($admins as $admin)
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="openAdminModal({{ $admin->id }})">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-medium">
                                        {{ strtoupper(substr($admin->name, 0, 1)) }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $admin->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $admin->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $admin->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $admin->role === 'super_admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $admin->role)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $admin->phone ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($admin->status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    View
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <h3 class="text-sm font-medium text-gray-900 mb-1">No administrators found</h3>
                                    <p class="text-sm text-gray-500">Get started by adding your first administrator.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ $admins->count() }} administrators
                    </div>
                    <div class="flex space-x-2">
                        {{ $admins->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Admin Details Modal -->
<div id="adminModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center overflow-y-auto">
    <div id="modalContent" class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-0 relative transform scale-95 opacity-0 transition-all duration-300 max-h-[90vh] overflow-hidden">

        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-900">Administrator Details</h3>
            <button type="button" onclick="closeAdminModal()" 
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Content -->
        <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
            <!-- Admin Info -->
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Administrator Information</h4>
                <div id="adminDetails" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
            </div>

            <!-- Transactions -->
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h4>
                <div id="transactions" class="max-h-64 overflow-y-auto space-y-3"></div>
            </div>

            <!-- Update Form -->
            <div class="p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Update Administrator</h4>
                <form id="updateAdminForm" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                            <select name="role" id="adminRole" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="admin">Administrator</option>
                                <option value="super_admin">Super Administrator</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" id="adminStatus" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password (Optional)</label>
                            <input type="password" name="password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <input type="password" name="password_confirmation" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update Administrator
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
                <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                            ${admin.name ? admin.name.charAt(0).toUpperCase() : 'A'}
                        </div>
                        <div>
                            <h4 class="text-lg font-medium text-gray-900">${admin.name}</h4>
                            <p class="text-sm text-gray-600">${admin.email}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Role</label>
                            <p class="text-sm text-gray-900 capitalize">${admin.role.replace('_', ' ')}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Phone</label>
                            <p class="text-sm text-gray-900">${admin.phone ?? 'N/A'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Status</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                ${admin.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${admin.status === 'active' ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Joined</label>
                            <p class="text-sm text-gray-900">${admin.created_at ? new Date(admin.created_at).toLocaleDateString('en-US', { 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric' 
                            }) : 'N/A'}</p>
                        </div>
                    </div>
                </div>
            `;

            // Fill Transactions
            let txHtml = '';
            if (admin.transactions && admin.transactions.length > 0) {
                txHtml = admin.transactions.map(tx => {
                    const statusClass = tx.status === 'success' || tx.status === 'completed'
                        ? 'bg-green-50 border-green-200 text-green-800'
                        : tx.status === 'pending'
                        ? 'bg-yellow-50 border-yellow-200 text-yellow-800'
                        : 'bg-red-50 border-red-200 text-red-800';

                    return `
                    <div class="border rounded-lg p-4 ${statusClass}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium capitalize">${tx.type ?? 'Transaction'}</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                ${tx.status === 'success' || tx.status === 'completed' ? 'bg-green-100 text-green-800' : 
                                  tx.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'}">
                                ${tx.status}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold">₦${Number(tx.amount).toLocaleString()}</span>
                            <span class="text-gray-600">${tx.reference ?? 'N/A'}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            ${tx.created_at ? new Date(tx.created_at).toLocaleString() : 'N/A'}
                        </div>
                    </div>`;
                }).join('');
            } else {
                txHtml = `
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-gray-500 text-sm">No transactions found</p>
                    </div>
                `;
            }
            document.getElementById('transactions').innerHTML = txHtml;

            // Update Form Action
            document.getElementById('updateAdminForm').action = `${baseUrl}/app/admin/${admin.id}/update`;

            // Set current values
            document.getElementById('adminRole').value = admin.role;
            document.getElementById('adminStatus').value = admin.status;

            // Show Modal
            const modal = document.getElementById('adminModal');
            const modalContent = document.getElementById('modalContent');
            modal.classList.remove('hidden');
            setTimeout(() => { 
                modalContent.classList.remove('scale-95', 'opacity-0'); 
                modalContent.classList.add('scale-100', 'opacity-100'); 
            }, 50);
        })
        .catch(error => {
            console.error('Error loading admin details:', error);
            alert('Failed to load administrator details. Please try again.');
        });
}

function closeAdminModal() {
    const modal = document.getElementById('adminModal');
    const modalContent = document.getElementById('modalContent');
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Close modal when clicking outside
document.getElementById('adminModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAdminModal();
    }
});
</script>

<style>
/* Custom scrollbar for transactions */
#transactions::-webkit-scrollbar {
    width: 6px;
}
#transactions::-webkit-scrollbar-thumb {
    background-color: #cbd5e0;
    border-radius: 3px;
}
#transactions::-webkit-scrollbar-track {
    background: transparent;
}
</style>
@endsection