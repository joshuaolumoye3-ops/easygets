@extends('layouts.admin')
@section('title', 'Manage eSIM Countries')

@section('content')
{{dd($countries)}}
<main class="pt-[70px] w-full flex flex-col">
    <div class="p-[1.875rem_1.75rem] w-full flex-grow">
        <h1 class="text-xl font-bold text-white mb-6">Manage eSIM Countries</h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($countries as $country)
                <div class="bg-[#191c24] text-white p-4 rounded-xl shadow relative">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img src="{{ $country->flag_url }}" class="w-8 h-6 rounded-sm" alt="">
                            <h2 class="font-semibold text-lg">{{ $country->country_name }}</h2>
                        </div>
                    </div>

                    <div class="mt-3">
                        <form class="flex items-center gap-2 update-price-form" data-id="{{ $country->id }}">
                            <input type="number" step="0.01" name="base_price"
                                value="{{ $country->base_price }}" class="text-black px-2 py-1 rounded w-24">
                            <button class="bg-green-600 px-3 py-1 rounded text-sm">Save</button>
                        </form>
                    </div>

                    <button
                        class="mt-3 bg-blue-600 w-full py-1 rounded-lg text-sm"
                        onclick="openPackages({{ $country->id }}, '{{ $country->country_name }}')">
                        Manage Packages
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</main>

<!-- Packages Modal -->
<div id="packagesModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50">
    <div class="bg-[#191c24] p-6 rounded-2xl text-white w-[90%] md:w-[500px]">
        <h2 id="modalTitle" class="text-lg font-bold mb-4"></h2>
        <div id="packagesList" class="space-y-3 max-h-[300px] overflow-y-auto mb-4"></div>

        <form id="addPackageForm" class="space-y-3">
            <input type="hidden" name="package_id" id="packageId">
            <input type="text" name="plan" placeholder="Plan (e.g. 5GB)" class="w-full px-3 py-2 rounded text-black">
            <input type="number" name="validity_days" placeholder="Validity (days)" class="w-full px-3 py-2 rounded text-black">
            <input type="number" step="0.01" name="price" placeholder="Price (₦)" class="w-full px-3 py-2 rounded text-black">
            <button type="submit" class="w-full bg-green-600 py-2 rounded-lg">Save Package</button>
        </form>

        <button onclick="closeModal()" class="mt-4 text-sm text-gray-300 underline">Close</button>
    </div>
</div>

<script>
async function openPackages(id, name) {
    const modal = document.getElementById('packagesModal');
    document.getElementById('modalTitle').innerText = `Packages for ${name}`;
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const res = await fetch(`/admin/esim/${id}/packages`);
    const data = await res.json();
    const list = document.getElementById('packagesList');
    list.innerHTML = '';

    data.packages.forEach(p => {
        list.innerHTML += `
            <div class="flex justify-between bg-[#23272f] px-3 py-2 rounded">
                <div>
                    <strong>${p.plan}</strong> - ${p.validity_days} days — ₦${p.price}
                </div>
                <div class="flex gap-2">
                    <button class="text-yellow-400" onclick="editPackage(${p.id}, '${p.plan}', ${p.validity_days}, ${p.price})">Edit</button>
                    <button class="text-red-500" onclick="deletePackage(${p.id})">Delete</button>
                </div>
            </div>`;
    });

    document.getElementById('addPackageForm').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await fetch(`/admin/esim/${id}/packages/save`, {
            method: 'POST',
            body: formData,
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
        });
        if (res.ok) openPackages(id, name);
    };
}

function editPackage(id, plan, validity, price) {
    document.getElementById('packageId').value = id;
    document.querySelector('input[name=plan]').value = plan;
    document.querySelector('input[name=validity_days]').value = validity;
    document.querySelector('input[name=price]').value = price;
}

async function deletePackage(id) {
    if (confirm('Delete this package?')) {
        await fetch(`/admin/esim/packages/${id}`, {
            method: 'DELETE',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
        });
        document.querySelector(`#packagesList`).innerHTML = '';
        document.getElementById('packagesModal').classList.add('hidden');
        location.reload();
    }
}

function closeModal() {
    document.getElementById('packagesModal').classList.add('hidden');
}

document.querySelectorAll('.update-price-form').forEach(form => {
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const id = form.dataset.id;
        const formData = new FormData(form);
        await fetch(`/admin/esim/${id}/update-price`, {
            method: 'POST',
            body: formData,
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
        });
        alert('Price updated');
    });
});
</script>
@endsection
