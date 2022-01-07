@extends('adminlte::page')

@section('content')
  {{ Breadcrumbs::render("{$resources}.create") }}
  @include('app.master.categories.components.form', [
    'route' => route('category.store', request()->input()),
    'title' => __('app.categories.create.title')
  ])
@endsection
