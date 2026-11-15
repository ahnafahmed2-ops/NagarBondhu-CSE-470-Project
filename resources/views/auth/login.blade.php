@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<section class="auth">
  <h1>Log in</h1>
  <form method="POST" action="{{ route('login') }}" novalidate>
    @csrf
    <label>Email <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label>
    @error('email')<p class="field-error">{{ $message }}</p>@enderror
    <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
    @error('password')<p class="field-error">{{ $message }}</p>@enderror
    <button class="btn" type="submit">Log in</button>
  </form>
  <p class="muted">New here? <a href="{{ route('register') }}">Create an account</a></p>
</section>
@endsection
