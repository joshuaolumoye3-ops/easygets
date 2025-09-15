@extends('layouts.app')

@section('title', 'Airtime & Data Management')

@section('content')
<div class="flex flex-col lg:flex-row min-h-screen bg-brand-gray-light">
    <!-- Sidebar -->
    @include('partials.aside')

    <!-- Main Content -->
    <div class="flex-1 p-4 sm:p-6 md:p-10 space-y-8 lg:ml-0">
        <!-- Top Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center bg-white p-4 rounded-2xl shadow-md mt-16 lg:mt-0">
            <h1 class="text-lg sm:text-xl font-bold text-brand-green mb-4 md:mb-0 text-center md:text-left">Airtime & Data Management</h1>
            <div class="flex items-center space-x-4 w-full md:w-auto">
                <input 
                    type="text" 
                    placeholder="Search..." 
                    class="flex-1 md:flex-none px-4 py-2 w-full sm:w-auto rounded-lg border border-brand-gray-medium focus:outline-none focus:ring-2 focus:ring-brand-green-medium text-sm sm:text-base"
                >
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-brand-green-medium flex items-center justify-center text-white font-bold text-sm sm:text-base">A</div>
            </div>
        </div>

        <!-- Data Price Control Section -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="text-base sm:text-lg font-bold mb-4">Set Data Price Per GB</h2>
            <form class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <label class="flex-1 w-full">
                    <span class="block text-gray-600 mb-2 text-sm sm:text-base">Price Per GB (₦)</span>
                    <input 
                        type="number" 
                        placeholder="e.g. 20" 
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green-medium text-sm sm:text-base"
                    >
                </label>
                <button 
                    type="submit" 
                    class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 bg-brand-green text-white rounded-lg hover:bg-brand-green-medium text-sm sm:text-base"
                >
                    Save
                </button>
            </form>
        </div>

        <!-- Recent Data Requests -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="text-base sm:text-lg font-bold mb-4">Recent Data Requests</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm sm:text-base">
                    <thead>
                        <tr class="border-b border-brand-gray-medium">
                            <th class="p-2 sm:p-3">User</th>
                            <th class="p-2 sm:p-3">Plan</th>
                            <th class="p-2 sm:p-3">Amount</th>
                            <th class="p-2 sm:p-3">Status</th>
                            <th class="p-2 sm:p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-2 sm:p-3">John Doe</td>
                            <td class="p-2 sm:p-3">MTN - 1GB</td>
                            <td class="p-2 sm:p-3">₦320</td>
                            <td class="p-2 sm:p-3 text-green-600">Success</td>
                            <td class="p-2 sm:p-3">2025-09-13</td>
                        </tr>
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-2 sm:p-3">Jane Smith</td>
                            <td class="p-2 sm:p-3">Airtel - 2GB</td>
                            <td class="p-2 sm:p-3">₦640</td>
                            <td class="p-2 sm:p-3 text-red-600">Failed</td>
                            <td class="p-2 sm:p-3">2025-09-12</td>
                        </tr>
                        <tr>
                            <td class="p-2 sm:p-3">Michael Lee</td>
                            <td class="p-2 sm:p-3">GLO - 5GB</td>
                            <td class="p-2 sm:p-3">₦1600</td>
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
