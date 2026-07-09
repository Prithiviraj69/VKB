<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | Vishwakarma Bandhu</title>
    <!-- Tailwind CSS -->
    <script src="{{ (str_contains(request()->root(), 'localhost') || str_contains(request()->root(), '127.0.0.1')) ? asset('js/tailwind.js') : asset('public/js/tailwind.js') }}"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon -->
    <link rel="icon" type="image/jpg" href="{{ (str_contains(request()->root(), 'localhost') || str_contains(request()->root(), '127.0.0.1')) ? asset('logo.jpg') : asset('public/logo.jpg') }}">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6 bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 border border-gray-150/20 animate-fade-in space-y-6">
        
        <!-- Logo and Header -->
        <div class="text-center">
            <img src="{{ (str_contains(request()->root(), 'localhost') || str_contains(request()->root(), '127.0.0.1')) ? asset('logo.jpg') : asset('public/logo.jpg') }}" alt="Vishwakarma Bandhu Logo" class="w-24 h-24 rounded-2xl mx-auto mb-4 object-cover border border-orange-100 shadow-md" />
            <h1 class="text-xl font-black text-gray-800 tracking-wide">Change Password</h1>
            <p class="text-xs text-orange-600 font-bold uppercase mt-1 tracking-wider">One-Time Security Setup</p>
        </div>

        <!-- Info Alert -->
        <div class="p-4 bg-orange-50 border border-orange-200 rounded-xl text-orange-900 text-xs flex gap-3 shadow-inner">
            <i class="fa-solid fa-shield-halved text-lg text-orange-600 shrink-0"></i>
            <div>
                <span class="font-bold">Password Update Required:</span>
                <p class="mt-0.5 leading-relaxed text-gray-650">Since your account was created by an administrator, you must choose a new, secure password before you can access the dashboard.</p>
            </div>
        </div>

        <!-- Validation Errors -->
        @if ($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-900 text-xs flex gap-3 shadow-inner">
                <i class="fa-solid fa-circle-exclamation text-base text-rose-600 shrink-0"></i>
                <div>
                    <span class="font-bold">Errors occurred:</span>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('force-change-password.update') }}" method="POST" class="space-y-4" onsubmit="return validatePasswords()">
            @csrf

            <!-- New Password -->
            <div>
                <label for="password" class="block text-xs font-bold text-gray-750 uppercase mb-2">New Password *</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400">
                        <i class="fa-solid fa-lock text-sm"></i>
                    </span>
                    <input type="password" name="password" id="password" required minlength="6" placeholder="At least 6 characters" class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition font-semibold" />
                </div>
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-gray-750 uppercase mb-2">Confirm New Password *</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400">
                        <i class="fa-solid fa-circle-check text-sm"></i>
                    </span>
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="6" placeholder="Re-enter password" class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition font-semibold" />
                </div>
                <p id="match_error" class="text-[10px] text-rose-600 font-bold mt-1.5 hidden">Passwords do not match.</p>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs shadow-md hover:shadow-lg transition flex items-center justify-center gap-1.5 mt-2">
                <i class="fa-solid fa-key"></i>
                <span>Save Password & Continue</span>
            </button>
        </form>

        <!-- Exit/Logout -->
        <div class="text-center pt-2 border-t border-gray-100">
            <a href="{{ route('logout') }}" class="text-xs text-gray-400 hover:text-rose-600 font-bold transition flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Cancel & Log Out</span>
            </a>
        </div>
    </div>

    <script>
        function validatePasswords() {
            const pass = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirmation').value;
            const errorElement = document.getElementById('match_error');

            if (pass !== confirm) {
                errorElement.classList.remove('hidden');
                return false;
            }
            errorElement.classList.add('hidden');
            return true;
        }
    </script>
</body>
</html>
