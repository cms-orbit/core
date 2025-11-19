@extends('settings::auth')
@section('title',__('Sign in to your account'))

@section('content')
    <h1 class="h4 text-body-emphasis mb-4">{{__('Sign in to your account')}}</h1>

    <form class="m-t-md"
          role="form"
          method="POST"
          data-controller="form"
          data-form-need-prevents-form-abandonment-value="false"
          data-action="form#submit"
          action="{{ route('settings.login.auth') }}">
        @csrf

        @includeWhen($isLockUser,'settings::auth.lockme')
        @includeWhen(!$isLockUser,'settings::auth.signin')
    </form>
@endsection
