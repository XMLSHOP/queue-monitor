@extends('admin.layout.default')

@section('title', trans('Monitor'). ': '. trans('Jobs'))

@section('styles')
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
@endsection

@section('body')
    @include('monitor::jobs.jobs-content')
@endsection
