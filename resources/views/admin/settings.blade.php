@extends('layouts.app')

@section('title', 'Platform Settings')

@section('content')
<div class="flex flex-col md:flex-row min-h-screen bg-brand-gray-light">
    <!-- Sidebar -->
    @include('partials.aside')

    <!-- Main Content -->
    <div class="flex-1 p-4 sm:p-6 md:p-10 space-y-6 md:space-y-8">
        <!-- Top Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center bg-white p-4 rounded-2xl shadow-md space-y-4 md:space-y-0">
            <h1 class="text-lg sm:text-xl font-bold text-brand-green">Platform Settings</h1>
            <div class="flex items-center space-x-3 w-full md:w-auto">
                <input type="text" placeholder="Search..." class="flex-1 md:flex-none px-3 sm:px-4 py-2 rounded-lg border border-brand-gray-medium focus:outline-none focus:ring-2 focus:ring-brand-green-medium">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-brand-green-medium flex items-center justify-center text-white font-bold">A</div>
            </div>
        </div>

        <!-- Add Admin Section -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="text-base sm:text-lg font-bold mb-4">Add New Admin</h2>
            <form class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <label>
                    <span class="block text-gray-600 mb-2">Full Name</span>
                    <input type="text" placeholder="Enter full name" class="w-full px-3 sm:px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green-medium">
                </label>
                <label>
                    <span class="block text-gray-600 mb-2">Email</span>
                    <input type="email" placeholder="Enter email" class="w-full px-3 sm:px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green-medium">
                </label>
                <label>
                    <span class="block text-gray-600 mb-2">Phone Number</span>
                    <input type="text" placeholder="Enter phone number" class="w-full px-3 sm:px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green-medium">
                </label>
                <label>
                    <span class="block text-gray-600 mb-2">Role</span>
                    <select class="w-full px-3 sm:px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green-medium">
                        <option>Admin</option>
                        <option>Super Admin</option>
                    </select>
                </label>
                <label class="md:col-span-2">
                    <span class="block text-gray-600 mb-2">Password</span>
                    <input type="password" placeholder="Enter password" class="w-full px-3 sm:px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green-medium">
                </label>
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="px-5 sm:px-6 py-2 sm:py-3 bg-brand-green text-white rounded-lg hover:bg-brand-green-medium">Add Admin</button>
                </div>
            </form>
        </div>

        <!-- Admin List -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="text-base sm:text-lg font-bold mb-4">Admins List</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm sm:text-base">
                    <thead>
                        <tr class="border-b border-brand-gray-medium">
                            <th class="p-2 sm:p-3">Name</th>
                            <th class="p-2 sm:p-3">Email</th>
                            <th class="p-2 sm:p-3">Phone</th>
                            <th class="p-2 sm:p-3">Role</th>
                            <th class="p-2 sm:p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-brand-gray-medium">
                            <td class="p-2 sm:p-3">John Doe</td>
                            <td class="p-2 sm:p-3">john@example.com</td>
                            <td class="p-2 sm:p-3">08012345678</td>
                            <td class="p-2 sm:p-3">Super Admin</td>
                            <td class="p-2 sm:p-3 space-x-1 sm:space-x-2">
                                <button class="px-3 sm:px-4 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Edit</button>
                                <button class="px-3 sm:px-4 py-1 bg-red-500 text-white rounded-lg hover:bg-red-600">Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td class="p-2 sm:p-3">Jane Smith</td>
                            <td class="p-2 sm:p-3">jane@example.com</td>
                            <td class="p-2 sm:p-3">08123456789</td>
                            <td class="p-2 sm:p-3">Admin</td>
                            <td class="p-2 sm:p-3 space-x-1 sm:space-x-2">
                                <button class="px-3 sm:px-4 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Edit</button>
                                <button class="px-3 sm:px-4 py-1 bg-red-500 text-white rounded-lg hover:bg-red-600">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Other Settings -->
        <div class="bg-white p-4 sm:p-6 rounded-2xl shadow-lg">
            <h2 class="text-base sm:text-lg font-bold mb-4">Other Platform Settings</h2>
            <form class="space-y-4">
                <label>
                    <span class="block text-gray-600 mb-2">Platform Name</span>
                    <input type="text" value="VTU Platform" class="w-full px-3 sm:px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green-medium">
                </label>
                <label>
                    <span class="block text-gray-600 mb-2">Support Email</span>
                    <input type="email" value="support@example.com" class="w-full px-3 sm:px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-green-medium">
                </label>
                <div class="flex justify-end">
                    <button type="submit" class="px-5 sm:px-6 py-2 sm:py-3 bg-brand-green text-white rounded-lg hover:bg-brand-green-medium">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
