@extends('layouts.app')

@section('title', 'Electricity Bills Management')

@section('content')
<div class="flex flex-col md:flex-row min-h-screen bg-brand-gray-light">
    <!-- Sidebar -->
    @include('partials.aside')

    <!-- Main Content -->
    <div class="flex-1 p-4 sm:p-6 md:p-10 space-y-6 md:space-y-8">
        <!-- Top Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center bg-white p-4 rounded-2xl shadow-md space-y-4 md:space-y-0">
            <h1 class="text-lg sm:text-xl font-bold text-brand-green">Electricity Bills Management</h1>
            <div class="flex items-center space-x-3 w-full md:w-auto">
                <input type="text" placeholder="Search..." class="flex-1 md:flex-none px-3 sm:px-4 py-2 rounded-lg border border-brand-gray-medium focus:outline-none focus:ring-2 focus:ring-brand-green-medium text-sm sm:text-base">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-brand-green-medium flex items-center justify-center text-white font-bold text-sm sm:text-base">A</div>
            </div>
        </div>

        <!-- Disco Pricing Control -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="text-base sm:text-lg font-bold mb-4">Set Service Charge per Disco</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm sm:text-base">
                    <thead>
                        <tr class="border-b border-brand-gray-medium">
                            <th class="p-2 sm:p-3">Disco</th>
                            <th class="p-2 sm:p-3">Current Charge</th>
                            <th class="p-2 sm:p-3">Update Charge</th>
                            <th class="p-2 sm:p-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-2 sm:p-3">IKEDC</td>
                            <td class="p-2 sm:p-3">₦100</td>
                            <td class="p-2 sm:p-3"><input type="number" placeholder="e.g. 120" class="w-full md:w-28 px-2 sm:px-3 py-2 border rounded-lg text-sm sm:text-base"></td>
                            <td class="p-2 sm:p-3"><button class="w-full md:w-auto px-3 sm:px-4 py-2 bg-brand-green text-white rounded-lg hover:bg-brand-green-medium text-sm sm:text-base">Update</button></td>
                        </tr>
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-2 sm:p-3">AEDC</td>
                            <td class="p-2 sm:p-3">₦100</td>
                            <td class="p-2 sm:p-3"><input type="number" placeholder="e.g. 150" class="w-full md:w-28 px-2 sm:px-3 py-2 border rounded-lg text-sm sm:text-base"></td>
                            <td class="p-2 sm:p-3"><button class="w-full md:w-auto px-3 sm:px-4 py-2 bg-brand-green text-white rounded-lg hover:bg-brand-green-medium text-sm sm:text-base">Update</button></td>
                        </tr>
                        <tr>
                            <td class="p-2 sm:p-3">EEDC</td>
                            <td class="p-2 sm:p-3">₦100</td>
                            <td class="p-2 sm:p-3"><input type="number" placeholder="e.g. 130" class="w-full md:w-28 px-2 sm:px-3 py-2 border rounded-lg text-sm sm:text-base"></td>
                            <td class="p-2 sm:p-3"><button class="w-full md:w-auto px-3 sm:px-4 py-2 bg-brand-green text-white rounded-lg hover:bg-brand-green-medium text-sm sm:text-base">Update</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Electricity Payments -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="text-base sm:text-lg font-bold mb-4">Recent Electricity Payments</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm sm:text-base">
                    <thead>
                        <tr class="border-b border-brand-gray-medium">
                            <th class="p-2 sm:p-3">User</th>
                            <th class="p-2 sm:p-3">Disco</th>
                            <th class="p-2 sm:p-3">Meter No</th>
                            <th class="p-2 sm:p-3">Amount</th>
                            <th class="p-2 sm:p-3">Status</th>
                            <th class="p-2 sm:p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-2 sm:p-3">John Doe</td>
                            <td class="p-2 sm:p-3">IKEDC</td>
                            <td class="p-2 sm:p-3">1234567890</td>
                            <td class="p-2 sm:p-3">₦5000</td>
                            <td class="p-2 sm:p-3 text-green-600">Success</td>
                            <td class="p-2 sm:p-3">2025-09-13</td>
                        </tr>
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-2 sm:p-3">Jane Smith</td>
                            <td class="p-2 sm:p-3">AEDC</td>
                            <td class="p-2 sm:p-3">9876543210</td>
                            <td class="p-2 sm:p-3">₦3000</td>
                            <td class="p-2 sm:p-3 text-red-600">Failed</td>
                            <td class="p-2 sm:p-3">2025-09-12</td>
                        </tr>
                        <tr>
                            <td class="p-2 sm:p-3">Michael Lee</td>
                            <td class="p-2 sm:p-3">EEDC</td>
                            <td class="p-2 sm:p-3">1122334455</td>
                            <td class="p-2 sm:p-3">₦7000</td>
                            <td class="p-2 sm:p-3 text-yellow-600">Pending</td>
                            <td class="p-2 sm:p-3">2025-09-11</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
