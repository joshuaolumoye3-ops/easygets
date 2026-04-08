@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<!-- Main Content -->
<main class="pt-[70px] w-full flex flex-col min-h-screen bg-gray-50">
    <div class="p-[1.875rem_1.75rem] w-full flex-grow">
        
        <!-- Top Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
            <div class="mb-4 md:mb-0">
                <h1 class="text-2xl font-bold text-gray-900 mb-1">Admin Dashboard</h1>
                <p class="text-gray-600 text-sm">Welcome back! Here's what's happening with your platform.</p>
            </div>
            <div class="flex items-center space-x-4 w-full md:w-auto">
                <div class="relative flex-1 md:flex-none md:w-80">
                    <input 
                        id="userSearch"
                        type="text" 
                        placeholder="Search users..." 
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

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Total Users</p>
                        <div class="flex items-center">
                            <h3 class="text-3xl font-bold text-gray-900 mb-0">{{ $users->count() }}</h3>
                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                +3.5%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Total Revenue</p>
                        <div class="flex items-center">
                            <h3 class="text-3xl font-bold text-gray-900 mb-0">₦{{ number_format($stats['total_revenue'] ?? 0, 2) }}</h3>
                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                +11%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-3 0-5 1-5 4s2 4 5 4 5-1 5-4-2-4-5-4z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12v4m0 0l2-2m-2 2l-2-2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Profit Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Net Profit</p>
                        <div class="flex items-center">
                            <h3 class="text-3xl font-bold text-gray-900 mb-0">₦{{ number_format($stats['total_profit'] ?? 0, 2) }}</h3>
                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                +7%
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            Today: ₦{{ number_format($stats['profit_today'] ?? 0, 2) }} • 
                            Week: ₦{{ number_format($stats['profit_week'] ?? 0, 2) }} • 
                            Month: ₦{{ number_format($stats['profit_month'] ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="p-3 bg-purple-50 rounded-lg">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium mb-1">Total Transactions</p>
                        <div class="flex items-center">
                            <h3 class="text-3xl font-bold text-gray-900 mb-0">{{ $stats['total_transactions'] ?? 0 }}</h3>
                            <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                +8.3%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-orange-50 rounded-lg">
                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

            <div class="flex-1 min-w-[200px] lg:flex-[1_1_22%] rounded-md bg-[#191c24] p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-muted text-sm mb-1">Pending Requests</p>
                        <div class="flex items-center">
                            <h3 class="text-2xl font-bold text-white mb-0">{{ $stats['pending_requests'] ?? 0 }}</h3>
                            <p class="text-modern-warning text-sm ml-2 mb-0 font-medium">-2.4%</p>
                        </div>
                    </div>
                    <div class="text-modern-warning p-2 flex items-center justify-center w-[40px] h-[37px] bg-modern-warning/15 rounded-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users and Recent Activity Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Users Section -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">All Users</h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $users->count() }} total
                        </span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="usersTable" class="w-full text-left border-collapse text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wallet Balance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($users->take(10) as $user)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-medium text-sm">
                                            {{ strtoupper(substr($user->username, 0, 1)) }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $user->username }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $user->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $user->phone_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ₦{{ number_format(optional($user->wallet)->balance, 2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button 
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                        onclick="openUserModal({{ $user->id }})"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing {{ $users->take(10)->count() }} of {{ $users->count() }} users
                        </div>
                        <div class="flex space-x-2">
                            {{ $users->links() }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats & Activity -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    
                    <!-- Recent Activity -->
                    <div class="space-y-4">
                        <div class="flex items-center p-3 bg-green-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900">New User Registered</p>
                                <p class="text-xs text-gray-500">{{ $stats['latest_user']->created_at->diffForHumans() ?? 'No recent users' }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    +1
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-3 0-5 1-5 4s2 4 5 4 5-1 5-4-2-4-5-4z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12v4m0 0l2-2m-2 2l-2-2"/>
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-900">Latest Transaction</p>
                                <p class="text-xs text-gray-500">{{ $stats['latest_transaction']->created_at->diffForHumans() ?? 'No recent transactions' }}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    ₦{{ number_format($stats['latest_transaction']->amount ?? 0, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Stats -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">System Overview</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Success Rate</span>
                                <span class="font-medium text-gray-900">
                                    {{ $transactions->count() > 0 ? round(($stats['successful_transactions'] / $transactions->count()) * 100, 1) : 0 }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full transition-all duration-300" style="width: {{ $transactions->count() > 0 ? round(($stats['successful_transactions'] / $transactions->count()) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Active Users (30d)</span>
                                <span class="font-medium text-gray-900">
                                    {{ $stats['active_users_30_days'] }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: {{ $users->count() > 0 ? round(($stats['active_users_30_days'] / $users->count()) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Transactions</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                        {{ $transactions->count() }} total
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($transactions->take(10) as $tx)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-medium text-sm">
                                        {{ strtoupper(substr($tx->user->username ?? 'N', 0, 1)) }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $tx->user->username ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $tx->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                ₦{{ number_format($tx->amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($tx->status == 'success' || $tx->status == 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Success
                                    </span>
                                @elseif($tx->status == 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        Pending
                                    </span>
                                @elseif($tx->status == 'failed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        Failed
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $tx->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $tx->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ $transactions->take(10)->count() }} of {{ $transactions->count() }} transactions
                    </div>
                    <div class="flex space-x-2">
                        {{-- {{ $transactions->links() }} --}}
                    </div>
                </div>
            </div>
        </div>
<!-- User Details Modal -->
<div id="userModal" 
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center overflow-y-auto">
    <div id="modalContent" 
        class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-0 relative transform scale-95 opacity-0 transition-all duration-300 max-h-[90vh] overflow-hidden">

        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-900">User Management</h3>
            <button type="button" onclick="closeUserModal()" 
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Content -->
        <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
            <!-- User Details -->
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-lg font-medium text-gray-900 mb-4">User Information</h4>
                <div id="userDetails" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
            </div>

            <!-- Admin Actions -->
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Admin Actions</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <button type="button" onclick="showPasswordForm()" 
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        Change Password
                    </button>
                    <button type="button" onclick="showPinForm()" 
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Change PIN
                    </button>
                    <button type="button" onclick="showFundsForm()" 
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Manage Funds
                    </button>
                </div>

                <!-- Password Form -->
                <div id="passwordForm" class="hidden space-y-4 p-4 bg-gray-50 rounded-lg">
                    <h5 class="text-sm font-medium text-gray-900">Change Password</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" id="newPassword" placeholder="Enter new password" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <input type="password" id="confirmPassword" placeholder="Confirm new password" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <button type="button" onclick="updatePassword()" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Update Password
                    </button>
                </div>

                <!-- PIN Form -->
                <div id="pinForm" class="hidden space-y-4 p-4 bg-gray-50 rounded-lg">
                    <h5 class="text-sm font-medium text-gray-900">Change PIN</h5>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New 4-digit PIN</label>
                        <input type="password" id="newPin" placeholder="Enter 4-digit PIN" maxlength="4" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <button type="button" onclick="updatePin()" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                        Update PIN
                    </button>
                </div>

                <!-- Funds Management Form -->
                <div id="fundsForm" class="hidden space-y-4 p-4 bg-gray-50 rounded-lg">
                    <h5 class="text-sm font-medium text-gray-900">Wallet Management</h5>
                    
                    <!-- Add Funds Section -->
                    <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                        <h6 class="text-sm font-medium text-green-800 mb-3">Add Funds</h6>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                                <input type="number" id="addAmount" placeholder="Amount to add" min="0" step="0.01" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea id="addDescription" placeholder="Reason for adding funds" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none" rows="2"></textarea>
                            </div>
                            <button type="button" onclick="addFunds()" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                                Add Funds
                            </button>
                        </div>
                    </div>
                    
                    <!-- Deduct Funds Section -->
                    <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                        <h6 class="text-sm font-medium text-red-800 mb-3">Deduct Funds</h6>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                                <input type="number" id="deductAmount" placeholder="Amount to deduct" min="0" step="0.01" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea id="deductDescription" placeholder="Reason for deducting funds" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none" rows="2"></textarea>
                            </div>
                            <button type="button" onclick="deductFunds()" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                                Deduct Funds
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Feedback -->
                <div id="actionMessage" class="mt-4 p-3 rounded-lg text-sm font-medium"></div>
            </div>

            <!-- User Transactions -->
            <div class="p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">Recent Transactions</h4>
                <div id="userTransactions" class="overflow-x-auto"></div>
            </div>
        </div>
    </div>
</div>

<script>
let currentUserId = null;

// Get CSRF token function
function getCSRFToken() {
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    if (!tokenElement) {
        console.error('CSRF token not found. Make sure you have <meta name="csrf-token" content="{{ csrf_token() }}"> in your layout head.');
        return null;
    }
    return tokenElement.getAttribute('content');
}

// Generic request function with better error handling
async function makeRequest(url, data) {
    const token = getCSRFToken();
    if (!token) {
        throw new Error('CSRF token not found');
    }

    console.log(`Making request to: ${url}`, data); // Debug log

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    });

    console.log('Response status:', response.status); // Debug log

    if (!response.ok) {
        const errorText = await response.text();
        console.error('Response error:', errorText);
        throw new Error(`HTTP ${response.status}: ${errorText}`);
    }

    return response.json();
}

function showPasswordForm() {
    hideAllForms();
    document.getElementById('passwordForm').classList.remove('hidden');
    document.getElementById('actionMessage').innerHTML = '';
}

function showPinForm() {
    hideAllForms();
    document.getElementById('pinForm').classList.remove('hidden');
    document.getElementById('actionMessage').innerHTML = '';
}

function showFundsForm() {
    hideAllForms();
    document.getElementById('fundsForm').classList.remove('hidden');
    document.getElementById('actionMessage').innerHTML = '';
}

function hideAllForms() {
    document.getElementById('passwordForm').classList.add('hidden');
    document.getElementById('pinForm').classList.add('hidden');
    document.getElementById('fundsForm').classList.add('hidden');
}

function showMessage(message, isSuccess = false) {
    const messageDiv = document.getElementById('actionMessage');
    const colorClass = isSuccess ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200';
    messageDiv.className = `mt-4 p-3 rounded-lg text-sm font-medium border ${colorClass}`;
    messageDiv.innerHTML = message;
}
}

async function addFunds() {
    const amount = parseFloat(document.getElementById('addAmount').value);
    const description = document.getElementById('addDescription').value.trim();

    if (!amount || amount <= 0) {
        showMessage('Please enter a valid amount');
        return;
    }

    if (!description) {
        showMessage('Please provide a description');
        return;
    }

    if (!currentUserId) {
        showMessage('No user selected');
        return;
    }

    try {
        showMessage('Processing...', true);

        const baseUrl = '{{ url('/') }}';
        const data = await makeRequest(`${baseUrl}/app/admin/users/${currentUserId}/add-funds`, {
            amount: amount,
            description: description
        });

        if (data.success) {
            showMessage(data.message, true);
            document.getElementById('addAmount').value = '';
            document.getElementById('addDescription').value = '';
            // Refresh user details after 1 second
            setTimeout(() => {
                openUserModal(currentUserId);
            }, 1000);
        } else {
            showMessage(data.message || 'Failed to add funds');
        }
    } catch (error) {
        console.error('Add funds error:', error);
        showMessage('Failed to add funds: ' + error.message);
    }
}

async function deductFunds() {
    const amount = parseFloat(document.getElementById('deductAmount').value);
    const description = document.getElementById('deductDescription').value.trim();

    if (!amount || amount <= 0) {
        showMessage('Please enter a valid amount');
        return;
    }

    if (!description) {
        showMessage('Please provide a description');
        return;
    }

    if (!currentUserId) {
        showMessage('No user selected');
        return;
    }

    try {
        showMessage('Processing...', true);

        const baseUrl = '{{ url('/') }}';
        const data = await makeRequest(`${baseUrl}/app/admin/users/${currentUserId}/deduct-funds`, {
            amount: amount,
            description: description
        });

        if (data.success) {
            showMessage(data.message, true);
            document.getElementById('deductAmount').value = '';
            document.getElementById('deductDescription').value = '';
            // Refresh user details after 1 second
            setTimeout(() => {
                openUserModal(currentUserId);
            }, 1000);
        } else {
            showMessage(data.message || 'Failed to deduct funds');
        }
    } catch (error) {
        console.error('Deduct funds error:', error);
        showMessage('Failed to deduct funds: ' + error.message);
    }
}

async function updatePassword() {
    const password = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;

    if (!password || !confirm) {
        showMessage('Please fill in both password fields');
        return;
    }

    if (password !== confirm) {
        showMessage('Passwords do not match');
        return;
    }

    if (!currentUserId) {
        showMessage('No user selected');
        return;
    }

    try {
        showMessage('Processing...', true);

        const baseUrl = '{{ url('/') }}';
        const data = await makeRequest(`${baseUrl}/app/admin/users/${currentUserId}/update-password`, {
            password: password,
            password_confirmation: confirm
        });

        if (data.success) {
            showMessage(data.message, true);
            document.getElementById('passwordForm').classList.add('hidden');
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
        } else {
            showMessage(data.message || 'Failed to update password');
        }
    } catch (error) {
        console.error('Update password error:', error);
        showMessage('Failed to update password: ' + error.message);
    }
}

async function updatePin() {
    const pin = document.getElementById('newPin').value;
    
    if (!pin || pin.length !== 4) {
        showMessage('PIN must be exactly 4 digits');
        return;
    }

    if (!currentUserId) {
        showMessage('No user selected');
        return;
    }

    try {
        showMessage('Processing...', true);

        const baseUrl = '{{ url('/') }}';
        const data = await makeRequest(`${baseUrl}/app/admin/users/${currentUserId}/update-pin`, {
            pin: pin
        });

        if (data.success) {
            showMessage(data.message, true);
            document.getElementById('pinForm').classList.add('hidden');
            document.getElementById('newPin').value = '';
        } else {
            showMessage(data.message || 'Failed to update PIN');
        }
    } catch (error) {
        console.error('Update PIN error:', error);
        showMessage('Failed to update PIN: ' + error.message);
    }
}

async function openUserModal(userId) {
    currentUserId = userId;

    try {
        const baseUrl = '{{ url('/') }}';
        const response = await fetch(`${baseUrl}/app/admin/users/${userId}`);
        console.log('User details response status:', response.status);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        const user = data.user;
        const transactions = data.transactions;

        document.getElementById('userDetails').innerHTML = `
            <div class="space-y-3">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                        ${user.username ? user.username.charAt(0).toUpperCase() : 'U'}
                    </div>
                    <div>
                        <h4 class="text-lg font-medium text-gray-900">${user.first_name || ''} ${user.last_name || ''}</h4>
                        <p class="text-sm text-gray-600">@${user.username || 'N/A'}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Email</label>
                        <p class="text-sm text-gray-900">${user.email || 'N/A'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Phone</label>
                        <p class="text-sm text-gray-900">${user.phone_number || 'N/A'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Wallet Balance</label>
                        <p class="text-lg font-semibold text-green-600">₦${user.wallet ? Number(user.wallet.balance).toFixed(2) : '0.00'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Joined</label>
                        <p class="text-sm text-gray-900">${new Date(user.created_at).toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        })}</p>
                    </div>
                </div>
            </div>
        `;

        let txRows = '';
        if (transactions && transactions.length > 0) {
            txRows = transactions.map(tx => {
                let statusClass = 'bg-gray-100 text-gray-800';
                const status = tx.status ? tx.status.toLowerCase() : '';
                if (status === 'success' || status === 'completed') { 
                    statusClass = 'bg-green-100 text-green-800'; 
                } else if (status === 'pending') { 
                    statusClass = 'bg-yellow-100 text-yellow-800'; 
                } else if (status === 'failed') { 
                    statusClass = 'bg-red-100 text-red-800'; 
                }

                return `<tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${tx.type || 'N/A'}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">₦${Number(tx.amount || 0).toFixed(2)}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                            ${tx.status || 'Unknown'}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">${tx.created_at ? new Date(tx.created_at).toLocaleString() : 'N/A'}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">${tx.response || 'N/A'}</td>
                </tr>`;
            }).join('');
        } else {
            txRows = `<tr><td colspan="5" style="padding:8px; text-align:center; color:#bfbfbf;">No transactions yet.</td></tr>`;
        }

        document.getElementById('userTransactions').innerHTML = `
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${txRows}
                    </tbody>
                </table>
            </div>
        `;

        const modal = document.getElementById('userModal');
        const modalContent = document.getElementById('modalContent');
        modal.classList.remove('hidden');
        setTimeout(() => { 
            modalContent.classList.remove('scale-95', 'opacity-0'); 
            modalContent.classList.add('scale-100', 'opacity-100'); 
        }, 50);
        
    } catch (error) {
        console.error('Open modal error:', error);
        alert("Failed to load user details: " + error.message);
    }
}

function closeUserModal() {
    const modal = document.getElementById('userModal');
    const modalContent = document.getElementById('modalContent');
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        hideAllForms();
        document.getElementById('actionMessage').innerHTML = '';
        // Clear all form inputs
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
        document.getElementById('newPin').value = '';
        document.getElementById('addAmount').value = '';
        document.getElementById('addDescription').value = '';
        document.getElementById('deductAmount').value = '';
        document.getElementById('deductDescription').value = '';
        currentUserId = null;
    }, 200);
}

document.getElementById('userModal').addEventListener('click', e => {
    if (e.target === document.getElementById('userModal')) {
        closeUserModal();
    }
});
</script>
@endsection