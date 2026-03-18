@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="mb-0">My Employee Profile</h3>
            <div class="text-muted">View your employment information.</div>
        </div>
        <div>
            <a href="{{ route('employee.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Card 1: Account Information & Signature --}}
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-person-circle me-2"></i> Account Details
                </div>
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fs-2" style="width: 80px; height: 80px;">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                    </div>
                    <h5 class="card-title">{{ $user->name }}</h5>
                    <p class="card-text text-muted">{{ $user->email }}</p>

                    <div class="mt-3">
                        <span class="badge bg-secondary">
                            User ID: {{ $user->id }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- NEW: E-Signature Card --}}
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-pen me-2"></i> My E-Signature
                </div>
                <div class="card-body text-center">
                    @if($user->signature_path)
                        <div class="mb-3 border p-2 bg-light rounded">
                            <img src="{{ asset('storage/' . $user->signature_path) }}" alt="E-Signature" class="img-fluid" style="max-height: 100px;">
                        </div>
                        <p class="small text-success fw-bold"><i class="bi bi-check-circle me-1"></i> Signature uploaded</p>
                    @else
                        <div class="alert alert-warning small mb-3">
                            No e-signature found. Please upload one.
                        </div>
                    @endif

                    <form action="{{ route('employee.profile.signature.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3 text-start">
                            <label for="signature" class="form-label small fw-bold">Upload New Signature</label>
                            <input class="form-control form-control-sm @error('signature') is-invalid @enderror" type="file" id="signature" name="signature" accept=".png,.jpg,.jpeg" required>
                            @error('signature')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" style="font-size: 0.7rem;">Clear background PNG recommended. Max 2MB.</div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Save Signature</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Card 2: Employment Details --}}
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-briefcase me-2"></i> Employment Information
                </div>
                <div class="card-body">
                    @if($employee)
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-muted text-uppercase fw-bold">Position Title</label>
                                <div class="fs-5 border-bottom pb-1">{{ $employee->position_title ?? 'Not Assigned' }}</div>
                            </div>

                            <div class="col-md-3">
                                <label class="small text-muted text-uppercase fw-bold">Salary Grade</label>
                                <div class="fs-5 border-bottom pb-1">
                                    {{ $employee->salary_grade ? 'SG ' . $employee->salary_grade : 'N/A' }}
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="small text-muted text-uppercase fw-bold">Sex</label>
                                <div class="fs-5 border-bottom pb-1">
                                    {{ $employee->sex ? $employee->sex : '—' }}
                                </div>
                            </div>

                            <div class="col-md-6 pt-3">
                                <label class="small text-muted text-uppercase fw-bold">Office / Department</label>
                                <div class="fs-5 border-bottom pb-1">{{ $employee->office->name ?? 'Pending Assignment' }}</div>
                            </div>

                            <div class="col-md-6 pt-3">
                                <label class="small text-muted text-uppercase fw-bold">Division / Unit</label>
                                <div class="fs-5 border-bottom pb-1">{{ $employee->division->name ?? 'Pending Assignment' }}</div>
                            </div>

                            <div class="col-md-12 pt-3">
                                <label class="small text-muted text-uppercase fw-bold">Employment Status</label>
                                <div>
                                    @php
                                        $status = $employee->status ?? 'unknown';
                                        $badgeColor = match($status) {
                                            'active' => 'success',
                                            'inactive' => 'secondary',
                                            'suspended' => 'danger',
                                            default => 'warning'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeColor }} fs-6 px-3 py-2 text-capitalize">
                                        {{ $status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Your employee profile has not been set up yet. Please contact the administrator.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
