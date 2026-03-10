@extends('layouts.app')
@section('content')
<div class="container py-4">
    <h3 class="mb-4">Approver Dashboard</h3>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Pending Requests</div>
                        <div class="fs-1 fw-bold text-dark">{{ $stats['pending'] }}</div>
                    </div>
                    <i class="bi bi-inbox fs-1 text-warning"></i>
                </div>
                <div class="card-footer bg-white">
                    <a href="{{ route('approver.inbox') }}" class="text-decoration-none small text-warning fw-bold">
                        Go to Inbox <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Processed Total</div>
                        <div class="fs-1 fw-bold text-dark">{{ $stats['processed'] }}</div>
                    </div>
                    <i class="bi bi-check2-all fs-1 text-primary"></i>
                </div>
                <div class="card-footer bg-white">
                    <a href="{{ route('approver.reports.myActions') }}" class="text-decoration-none small text-primary fw-bold">
                        View History <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Demographics for Approver --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-4 border-info h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold mb-2">Workforce</div>
                    <div class="d-flex justify-content-between align-items-center px-2">
                        <div class="text-center">
                            <div class="fs-3 fw-bold bi bi-gender-male text-primary">{{ $stats['male'] ?? 0 }}</div>
                            <small class="text-muted">Male</small>
                        </div>
                        <div class="vr" style="height: 40px;"></div>
                        <div class="text-center">
                            <div class="fs-3 fw-bold bi bi-gender-female text-danger">{{ $stats['female'] ?? 0 }}</div>
                            <small class="text-muted">Female</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white text-muted small">
                    Active Employees
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
