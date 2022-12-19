@extends('admin.layout.default')

@section('title', trans('Monitor'). ': '. trans('Schedulers'))

@section('styles')
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
@endsection

@section('body')
    @include('monitor::schedulers.schedulers-content')
@endsection
