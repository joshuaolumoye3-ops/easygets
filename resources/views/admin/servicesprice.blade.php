@extends('layouts.admin')
@section('title', 'Manage Prices')

@section('content')
<main style="padding-top:70px; width:100%; display:flex; flex-direction:column; background:#0f1115; min-height:100vh;">

    <div style="padding:30px 28px; width:100%; flex-grow:1;">

        <h1 style="font-size:24px; font-weight:700; color:#ffffff; margin-bottom:25px;">
            Plans for {{ $provider->name }}
        </h1>

        <!-- Bulk Save -->
        <form action="{{ route('admin.products.save', $provider->id) }}" method="POST"
            style="margin-bottom:25px; background:#1a1d23; padding:20px; border-radius:14px; color:#fff; box-shadow:0 8px 20px rgba(0,0,0,0.25);">
            @csrf

            <div style="display:flex; flex-wrap:wrap; gap:15px; align-items:center;">

                <input type="number" step="0.01" id="price_input" name="price_input"
                    @if ($provider->percentage == 1)
                        placeholder="Enter percentage (e.g., 70 for 70%)"
                    @else
                        placeholder="Enter client price"
                    @endif
                    required
                    style="flex:1; min-width:220px; padding:10px; border-radius:8px; border:none; outline:none; font-size:14px;">

                <input type="hidden" id="client_price" name="client_price">

                <button type="submit"
                    style="background:#22c55e; border:none; padding:10px 18px; border-radius:8px; color:#fff; font-weight:600; cursor:pointer; transition:0.2s;"
                    onmouseover="this.style.opacity='0.85'"
                    onmouseout="this.style.opacity='1'">
                    Save All Plans
                </button>
            </div>

            <script>
                document.querySelector('form').addEventListener('submit', function() {
                    const inputValue = parseFloat(document.getElementById('price_input').value) || 0;
                    const clientPriceInput = document.getElementById('client_price');

                    @if ($provider->percentage == 1)
                        clientPriceInput.value = (1 + (inputValue / 100)).toFixed(2);
                    @else
                        clientPriceInput.value = inputValue.toFixed(2);
                    @endif
                });
            </script>
        </form>

        <!-- Search -->
        <div style="margin-bottom:20px;">
            <input type="text" id="searchInput" placeholder="Search plans..."
                style="width:100%; max-width:350px; padding:10px; border-radius:8px; border:none; outline:none;">
        </div>

        <!-- Table -->
        <div style="background:#1a1d23; border-radius:14px; overflow:hidden; box-shadow:0 8px 20px rgba(0,0,0,0.25);">

            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; color:#fff;" id="productsTable">

                    <thead style="background:#222630;">
                        <tr>
                            <th style="padding:14px; text-align:left; font-size:14px;">Category</th>
                            <th style="padding:14px; text-align:left; font-size:14px;">Plan</th>
                            <th style="padding:14px; text-align:left; font-size:14px;">API Price</th>
                            <th style="padding:14px; text-align:left; font-size:14px;">Client Price</th>
                            <th style="padding:14px; text-align:left; font-size:14px;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($products as $product)
                        <tr style="border-top:1px solid rgba(255,255,255,0.05);">

                            <td style="padding:14px;">{{ $product->category }}</td>

                            <td style="padding:14px; font-weight:500;">{{ $product->name }}</td>

                            <td style="padding:14px;">
                                <span style="background:#065f46; padding:6px 10px; border-radius:8px; font-size:13px; font-weight:600;">
                                    ₦{{ number_format($product->api_price, 2) }}
                                </span>
                            </td>

                            <td style="padding:14px;">
                                @if ($product->id)

                                <form action="{{ route('admin.products.update', ['id' => $product->id]) }}"
                                    method="POST"
                                    style="display:flex; gap:8px; align-items:center;">
                                    @csrf

                                    <input type="number" step="0.01"
                                        id="price_input_{{ $product->id }}"
                                        name="price_input"
                                        value="{{ $provider->percentage == 1 ? ($product->client_price - 1) * 100 : $product->client_price }}"
                                        style="width:110px; padding:6px; border-radius:6px; border:none; outline:none; font-size:13px;">

                                    <input type="hidden" id="client_price_{{ $product->id }}" name="client_price">

                                    <button type="submit"
                                        style="background:#22c55e; border:none; padding:6px 10px; border-radius:6px; color:#fff; font-size:13px; cursor:pointer;">
                                        Save
                                    </button>
                                </form>

                                <script>
                                    document.getElementById('price_input_{{ $product->id }}').closest('form')
                                        .addEventListener('submit', function() {
                                            const inputValue = parseFloat(document.getElementById('price_input_{{ $product->id }}').value) || 0;

                                            @if ($provider->percentage == 1)
                                                document.getElementById('client_price_{{ $product->id }}').value = (1 + (inputValue / 100)).toFixed(2);
                                            @else
                                                document.getElementById('client_price_{{ $product->id }}').value = inputValue.toFixed(2);
                                            @endif
                                        });
                                </script>

                                @if(isset($product->is_active) && !$product->is_active)
                                    <div style="margin-top:5px; font-size:12px; color:#facc15;">Deactivated</div>
                                @endif

                                @else

                                <form action="{{ route('admin.products.create', $provider->id) }}"
                                    method="POST"
                                    style="display:flex; gap:8px;">
                                    @csrf

                                    <input type="hidden" name="product_code" value="{{ $product->product_code }}">
                                    <input type="hidden" name="name" value="{{ $product->name }}">
                                    <input type="hidden" name="category" value="{{ $product->category }}">
                                    <input type="hidden" name="api_price" value="{{ $product->api_price }}">

                                    <input type="number" step="0.01" name="client_price"
                                        required
                                        style="width:110px; padding:6px; border-radius:6px; border:none; outline:none; font-size:13px;">

                                    <button type="submit"
                                        style="background:#22c55e; border:none; padding:6px 10px; border-radius:6px; color:#fff; font-size:13px;">
                                        Save
                                    </button>
                                </form>

                                @endif
                            </td>

                            <td style="padding:14px;">

                                <div style="font-size:12px; color:#9ca3af; margin-bottom:6px;">
                                    {{ $product->updated_at->diffForHumans() }}
                                </div>

                                @if ($product->id)
                                    @if (isset($product->is_active) && !$product->is_active)
                                        <form action="{{ route('admin.products.toggle', ['id' => $product->id]) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                style="background:#3b82f6; border:none; padding:6px 10px; border-radius:6px; color:#fff; font-size:12px;">
                                                Reactivate
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.products.delete', ['id' => $product->id]) }}"
                                            method="POST"
                                            onsubmit="return confirm('Deactivate this product?');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                style="background:#ef4444; border:none; padding:6px 10px; border-radius:6px; color:#fff; font-size:12px;">
                                                Deactivate
                                            </button>
                                        </form>
                                    @endif
                                @endif

                            </td>

                        </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>

    </div>
</main>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        document.querySelectorAll('#productsTable tbody tr').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
</script>
@endsection