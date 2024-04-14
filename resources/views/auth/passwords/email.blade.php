<!-- resources/views/auth/passwords/email.blade.php -->

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div>
        <label for="email">Email Address</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
        @error('email')
        <span>{{ $message }}</span>
        @enderror
    </div>

    <div>
        <button type="submit">Send Password Reset Link</button>
    </div>
</form>
