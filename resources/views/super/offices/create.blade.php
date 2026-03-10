@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h3 class="mb-3">Create Office</h3>

  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('super.offices.store') }}">
        @csrf

        <div class="mb-3">
          <label class="form-label">Office Code</label>
          <input class="form-control" name="office_code" value="{{ old('office_code') }}" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Office Name</label>
          <input class="form-control" name="name" value="{{ old('name') }}" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Address</label>
          <input class="form-control" name="address" value="{{ old('address') }}">
        </div>

        <button class="btn btn-primary">Save</button>
        <a class="btn btn-outline-secondary" href="{{ route('super.offices.index') }}">Cancel</a>
      </form>
    </div>
  </div>
</div>
@endsection
