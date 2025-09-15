@extends('layouts.app')

@section('title', 'JAMB Pins Management')

@section('content')
<div class="flex flex-col md:flex-row min-h-screen bg-brand-gray-light">
    <!-- Sidebar -->
    @include('partials.aside')

    <!-- Main Content -->
    <div class="flex-1 p-4 sm:p-6 md:p-10 space-y-6 md:space-y-8">
        <!-- Top Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center bg-white p-4 rounded-2xl shadow-md space-y-4 md:space-y-0">
            <h1 class="text-lg sm:text-xl font-bold text-brand-green">JAMB Pins Management</h1>
            <div class="flex items-center space-x-3 w-full md:w-auto">
                <input type="text" placeholder="Search..." class="flex-1 md:flex-none px-3 sm:px-4 py-2 rounded-lg border border-brand-gray-medium focus:outline-none focus:ring-2 focus:ring-brand-green-medium text-sm sm:text-base">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-brand-green-medium flex items-center justify-center text-white font-bold text-sm sm:text-base">A</div>
            </div>
        </div>

        <!-- Pin Upload Section -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="text-base sm:text-lg font-bold mb-4">Upload New JAMB Pins</h2>
            <form class="flex flex-col md:flex-row items-center gap-4">
                <label class="flex-1 w-full">
                    <span class="block text-gray-600 mb-2 text-sm sm:text-base">Enter Pin</span>
                    <input type="text" placeholder="Enter JAMB Pin" class="w-full px-3 sm:px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green-medium text-sm sm:text-base">
                </label>
                <label class="flex-1 w-full">
                    <span class="block text-gray-600 mb-2 text-sm sm:text-base">Serial Number</span>
                    <input type="text" placeholder="Enter Serial Number" class="w-full px-3 sm:px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green-medium text-sm sm:text-base">
                </label>
                <button type="submit" class="w-full md:w-auto px-4 sm:px-6 py-2 sm:py-3 bg-brand-green text-white rounded-lg hover:bg-brand-green-medium text-sm sm:text-base">Upload</button>
            </form>
        </div>

        <!-- Available Pins Section -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="text-base sm:text-lg font-bold mb-4">Available JAMB Pins</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm sm:text-base">
                    <thead>
                        <tr class="border-b border-brand-gray-medium">
                            <th class="p-2 sm:p-3">Pin</th>
                            <th class="p-2 sm:p-3">Serial Number</th>
                            <th class="p-2 sm:p-3">Status</th>
                            <th class="p-2 sm:p-3">Uploaded Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-2 sm:p-3">1234-5678-9012</td>
                            <td class="p-2 sm:p-3">SN-001</td>
                            <td class="p-2 sm:p-3 text-green-600">Available</td>
                            <td class="p-2 sm:p-3">2025-09-13</td>
                        </tr>
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-2 sm:p-3">9876-5432-1098</td>
                            <td class="p-2 sm:p-3">SN-002</td>
                            <td class="p-2 sm:p-3 text-red-600">Used</td>
                            <td class="p-2 sm:p-3">2025-09-12</td>
                        </tr>
                        <tr>
                            <td class="p-2 sm:p-3">4567-8901-2345</td>
                            <td class="p-2 sm:p-3">SN-003</td>
                            <td class="p-2 sm:p-3 text-yellow-600">Pending</td>
                            <td class="p-2 sm:p-3">2025-09-11</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="text-base sm:text-lg font-bold mb-4">Recent JAMB Pin Purchases</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm sm:text-base">
                    <thead>
                        <tr class="border-b border-brand-gray-medium">
                            <th class="p-2 sm:p-3">User</th>
                            <th class="p-2 sm:p-3">Pin</th>
                            <th class="p-2 sm:p-3">Amount</th>
                            <th class="p-2 sm:p-3">Status</th>
                            <th class="p-2 sm:p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-2 sm:p-3">John Doe</td>
                            <td class="p-2 sm:p-3">1234-5678-9012</td>
                            <td class="p-2 sm:p-3">₦3500</td>
                            <td class="p-2 sm:p-3 text-green-600">Success</td>
                            <td class="p-2 sm:p-3">2025-09-13</td>
                        </tr>
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-2 sm:p-3">Jane Smith</td>
                            <td class="p-2 sm:p-3">9876-5432-1098</td>
                            <td class="p-2 sm:p-3">₦3500</td>
                            <td class="p-2 sm:p-3 text-red-600">Failed</td>
                            <td class="p-2 sm:p-3">2025-09-12</td>
                        </tr>
                        <tr>
                            <td class="p-2 sm:p-3">Michael Lee</td>
                            <td class="p-2 sm:p-3">4567-8901-2345</td>
                            <td class="p-2 sm:p-3">₦3500</td>
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
