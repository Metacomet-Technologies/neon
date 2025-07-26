@extends('mail.layouts.base')

@section('content')
    @include('mail.components.content')
        @yield('message')
    @endinclude
@endsection