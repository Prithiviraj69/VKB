<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VishwaKarma Bandhu Admin Portal</title>
    <!-- Tailwind CSS -->
    <script src="{{ (str_contains(request()->root(), 'localhost') || str_contains(request()->root(), '127.0.0.1')) ? asset('js/tailwind.js') : asset('public/js/tailwind.js') }}"></script>
    <!-- FontAwesome Icons -->
    <!-- Favicon -->
    <link rel="icon" type="image/jpg" href="{{ (str_contains(request()->root(), 'localhost') || str_contains(request()->root(), '127.0.0.1')) ? asset('logo.jpg') : asset('public/logo.jpg') }}">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6 bg-gradient-to-br from-orange-600 via-amber-500 to-slate-200">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 border border-gray-100 animate-fade-in">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <img src="{{ (str_contains(request()->root(), 'localhost') || str_contains(request()->root(), '127.0.0.1')) ? asset('logo.jpg') : asset('public/logo.jpg') }}" alt="Vishwakarma Bandhu Logo" class="w-28 h-28 rounded-2xl mx-auto mb-3 object-cover border border-orange-100 shadow-md" />
            <h1 class="text-xl font-black text-orange-600 tracking-wide">VishwaKarma Bandhu</h1>
            <p class="text-gray-400 text-[9px] font-black uppercase mt-1 tracking-wider">Admin Portal</p>
        </div>

        <!-- Validation Error Message Card -->
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-900 text-xs flex gap-3 shadow-inner">
                <i class="fa-solid fa-triangle-exclamation text-base text-red-600 shrink-0"></i>
                <div>
                    <span class="font-bold">Access Denied:</span>
                    <ul class="list-disc list-inside mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Login Form -->
        <form id="loginForm" action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Email field -->
            <div>
                <label for="email" class="block text-xs font-bold text-gray-700 uppercase mb-2">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-orange-600">
                        <i class="fa-regular fa-envelope text-lg"></i>
                    </span>
                    <input type="email" name="email" id="email" required value="{{ old('email') }}"
                           placeholder="name@example.com"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm transition" />
                </div>
            </div>

            <!-- Password field -->
            <div>
                <label for="password" class="block text-xs font-bold text-gray-700 uppercase mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-orange-600">
                        <i class="fa-solid fa-lock text-lg"></i>
                    </span>
                    <input type="password" name="password" id="password" required
                           placeholder="Enter your password"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm transition" />
                </div>
            </div>

            <!-- Remember Me checkbox -->
            <div class="flex items-center">
                <input type="checkbox" name="remember" id="remember" 
                       class="h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                <label for="remember" class="ml-2 text-xs font-semibold text-gray-600 select-none">Remember this account</label>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="submitBtn"
                    class="w-full py-3.5 bg-orange-600 text-white rounded-xl text-sm font-bold shadow-lg hover:bg-orange-500 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition duration-150 transform hover:-translate-y-0.5">
                Sign In
            </button>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.classList.add('opacity-60', 'cursor-not-allowed');
            btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin mr-2"></i> Signing In...';
        });
    </script>
</body>
</html>
