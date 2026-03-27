@extends('layouts.admin') 
@section('title', 'Manage Social Service Descriptions')

@section('content')
<main class="pt-[70px] w-full flex flex-col">
    <div class="p-[1.875rem_1.75rem] w-full flex-grow">
        
        <div class="flex flex-col md:flex-row justify-between items-center bg-[#191c24] p-4 rounded-2xl shadow-md mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-white">
                Social Service Descriptions
            </h1>
            <button onclick="refreshServices()" class="mt-2 md:mt-0 bg-brand-green text-white px-4 py-2 rounded-lg hover:bg-green-600 transition">
                Refresh Services
            </button>
        </div>

        @if(session('success'))
            <div class="bg-green-500 text-white p-4 rounded-lg mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-[#191c24] rounded-2xl shadow-md p-6">
            <div class="mb-4">
                <input 
                    type="text" 
                    id="searchInput" style="background:#23272f; color:white "
                    placeholder="Search services by name, category, or platform..." 
                    class="w-full px-4 py-2 bg-[#23272f] text-white border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green"
                    onkeyup="filterServices()"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto text-white">
                    <thead>
                        <tr class="border-b border-muted/20 text-muted text-sm">
                            <th class="text-start p-3">Service ID</th>
                            <th class="text-start p-3">Service Name</th>
                            <th class="text-start p-3">Admin %</th>
                            <th class="text-start p-3">Category</th>
                            <th class="text-start p-3">Type</th>
                            <th class="text-start p-3">Description</th>
                            <th class="text-center p-3">Action</th>
                        </tr>
                    </thead>
                    <tbody id="servicesTable">
                        @forelse($services as $service)
                            <tr class="border-b border-muted/20 hover:bg-[#23272f] service-row">
                                <td class="p-3 service-id">{{ $service['service_id'] }}</td>
                                <td class="p-3 service-name">
                                    {{ $service['name'] }}
                                    @if(!empty($service['custom_name']))
                                        <span class="ml-2 inline-block text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">(custom)</span>
                                    @endif
                                </td>
                                <td class="p-3 service-admin-percentage">{{ $service['admin_percentage'] ?? 0 }}%</td>
                                <td class="p-3 service-category">{{ $service['category'] }}</td>
                                <td class="p-3 service-type">{{ $service['type'] }}</td>
                                <td class="p-3">
                                    <span class="text-xs text-gray-400">
                                        {{ $service['description'] ? Str::limit($service['description'], 50) : 'No description' }}
                                    </span>
                                </td>
                                <td class="p-3 text-center">
                                    <button 
                                        onclick="openDescriptionModal('{{ $service['service_id'] }}', '{{ addslashes($service['name']) }}', {{ json_encode($service['description'] ?? '') }}, {{ json_encode($service['custom_name'] ?? '') }}, {{ json_encode($service['category'] ?? '') }}, {{ json_encode($service['admin_percentage'] ?? 0) }})"
                                        class="bg-brand-green text-white px-3 py-1 rounded hover:bg-green-600 transition text-sm"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-8 text-gray-400">
                                    No services available. Click "Refresh Services" to load.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Edit Description Modal -->
<div id="descriptionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-[#191c24] rounded-2xl shadow-xl w-full max-w-2xl mx-4 p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-white">Edit Service Description</h2>
            <button onclick="closeDescriptionModal()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
        </div>
        
        <form id="descriptionForm" method="POST" action="{{ route('admin.social.updateDescription') }}">
            @csrf
            <input type="hidden" name="service_id" id="modalServiceId">
            
            <div class="mb-4">
                <label class="block text-white font-semibold mb-2">Service Name (editable)</label>
                <input type="text" name="custom_name" id="modalServiceName" style="background:#23272f; color:white" class="w-full px-4 py-2 bg-[#23272f] text-white rounded-lg border border-gray-600 focus:outline-none focus:ring-2 focus:ring-brand-green" />
            </div>

            <div class="mb-4">
                <label class="block text-white font-semibold mb-2">Category (editable)</label>
                <input type="text" name="custom_category" id="modalServiceCategory" style="background:#23272f; color:white" class="w-full px-4 py-2 bg-[#23272f] text-white rounded-lg border border-gray-600 focus:outline-none focus:ring-2 focus:ring-brand-green" />
            </div>

            <div class="mb-4">
                <label class="block text-white font-semibold mb-2">Admin Markup (%)</label>
                <input type="number" name="admin_percentage" id="modalAdminPercentage" min="0" step="1" value="0" placeholder="e.g., 10" style="background:#23272f; color:white" class="w-full px-4 py-2 bg-[#23272f] text-white rounded-lg border border-gray-600 focus:outline-none focus:ring-2 focus:ring-brand-green" />
                <p class="text-xs text-gray-400 mt-1">Enter percentage to add to the base price (e.g., 10 = 10%)</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-white font-semibold mb-2">Description</label>
                <textarea 
                    name="description" 
                    id="modalDescription" 
                    rows="10" style="background:#23272f; color:white"
                    class="w-full px-4 py-2 bg-[#23272f] text-white border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green"
                    placeholder="Enter service description..."
                ></textarea>
                <p class="text-xs text-gray-400 mt-1">You can use line breaks for formatting</p>
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeDescriptionModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-brand-green text-white rounded-lg hover:bg-green-600 transition">
                    Save Description
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Setup CSRF token for all AJAX requests
const csrfToken = document.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.csrfToken = csrfToken.getAttribute('content');
}

function openDescriptionModal(serviceId, serviceName, description, customName = '', customCategory = '', adminPercentage = 0) {
    document.getElementById('modalServiceId').value = serviceId;
    // If admin has provided a custom name, show it; otherwise show the original API name
    document.getElementById('modalServiceName').value = customName || serviceName;
    document.getElementById('modalServiceCategory').value = customCategory || '';
    document.getElementById('modalAdminPercentage').value = adminPercentage || 0;
    document.getElementById('modalDescription').value = description || '';
    document.getElementById('descriptionModal').classList.remove('hidden');
    document.getElementById('descriptionModal').classList.add('flex');
}

function closeDescriptionModal() {
    document.getElementById('descriptionModal').classList.add('hidden');
    document.getElementById('descriptionModal').classList.remove('flex');
}

function filterServices() {
    const searchValue = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('.service-row');

    rows.forEach(row => {
        const serviceId = row.querySelector('.service-id').textContent.toLowerCase();
        const serviceName = row.querySelector('.service-name').textContent.toLowerCase();
        const serviceCategory = row.querySelector('.service-category').textContent.toLowerCase();
        const serviceType = row.querySelector('.service-type').textContent.toLowerCase();

        if (serviceId.includes(searchValue) || serviceName.includes(searchValue) ||
            serviceCategory.includes(searchValue) || serviceType.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function refreshServices() {
    window.location.href = "{{ route('admin.social.refresh') }}";
}

// Ensure form has fresh CSRF token on submit
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('descriptionForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Refresh CSRF token from meta tag
            const token = document.querySelector('meta[name="csrf-token"]');
            const csrfInput = form.querySelector('input[name="_token"]');
            if (token && csrfInput) {
                csrfInput.value = token.getAttribute('content');
            }
        });
    }
});
</script>
@endsection

