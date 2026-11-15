@extends('layouts.app')
@section('title', 'Create account')
@section('content')
<section class="auth">
  <h1>Create your account</h1>
  <form method="POST" action="{{ route('register') }}" novalidate>
    @csrf
    <label>Full name <input name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="100"></label>
    @error('name')<p class="field-error">{{ $message }}</p>@enderror
    <label>Email <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
    @error('email')<p class="field-error">{{ $message }}</p>@enderror
    <label>Phone <span class="muted">(optional)</span> <input name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel"></label>
    @error('phone')<p class="field-error">{{ $message }}</p>@enderror
    <label>Password <input type="password" name="password" autocomplete="new-password" minlength="8" required></label>
    @error('password')<p class="field-error">{{ $message }}</p>@enderror
    <label>Confirm password <input type="password" name="password_confirmation" autocomplete="new-password" required></label>
    <button class="btn" type="submit">Create account</button>
  </form>
  <p class="muted">Already registered? <a href="{{ route('login') }}">Log in</a></p>
</section>
@endsection
