@extends('layouts.app')

@section('content')
<div class="container py-4">

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h3 class="mb-0">Employee Dashboard</h3>
      <div class="text-muted small">Calendar view of your leave applications</div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-primary" href="{{ route('employee.leaves.create') }}">
        <i class="bi bi-plus-circle"></i> Apply Leave
      </a>
      <a class="btn btn-outline-secondary" href="{{ route('employee.leaves.index') }}">
        <i class="bi bi-clock-history"></i> Leave History
      </a>
    </div>
  </div>

  {{-- Quick stats (optional, safe defaults) --}}
  <div class="row g-3 mb-3">
    {{-- Card 1: Total Applications --}}
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-start border-4 border-primary">
        <div class="card-body">
          <div class="text-muted small text-uppercase fw-bold">Total Applications</div>
          <div class="fs-1 fw-bold text-dark">{{ $stats->total_applications ?? 0 }}</div>
        </div>
      </div>
    </div>

  {{-- Card 2: Pending --}}
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-start border-4 border-warning">
        <div class="card-body">
          <div class="text-muted small text-uppercase fw-bold">Pending Approval</div>
          <div class="fs-1 fw-bold text-dark">{{ $stats->pending ?? 0 }}</div>
        </div>
      </div>
    </div>

    {{-- Card 3: Approved (Leaves Already Did) --}}
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-start border-4 border-success">
        <div class="card-body">
          <div class="text-muted small text-uppercase fw-bold">Approved Leaves</div>
          <div class="fs-1 fw-bold text-dark">{{ $stats->approved ?? 0 }}</div>
          <div class="text-muted small">
             Leaves you have already taken.
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Calendar + Filter --}}
  <div class="card mb-3">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="fw-semibold">
        <i class="bi bi-calendar3 me-1"></i> Leave Calendar
      </div>

      <div class="d-flex align-items-center gap-2">
        <span class="small text-muted">Status:</span>
        <select id="statusFilter" class="form-select form-select-sm" style="width: 180px;">
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="returned">Returned</option>
          <option value="disapproved">Disapproved</option>
        </select>
      </div>
    </div>

    <div class="card-body">
      <div id="calendar"></div>
      <div class="mt-3 small text-muted">
        Click an event to open the leave application details.
      </div>
    </div>
  </div>

  {{-- Recent applications (keeps your existing feature) --}}
  <div class="card">
    <div class="card-header bg-white fw-semibold">Recent Leave Applications</div>
    <div class="card-body">
      @if(empty($leaves) || $leaves->count() === 0)
        <div class="alert alert-info mb-0">No leave applications yet.</div>
      @else
        <div class="table-responsive">
          <table class="table table-striped mb-0 align-middle">
            <thead>
              <tr>
                <th>Date Filed</th>
                <th>Leave Type</th>
                <th>Dates</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($leaves as $l)
              @php
                $status = strtolower($l->status ?? 'pending');
                $badge = match($status) {
                  'approved' => 'success',
                  'returned' => 'info',
                  'disapproved' => 'danger',
                  'cancelled' => 'secondary',
                  default => 'warning',
                };
                $text = ($badge === 'warning') ? 'text-dark' : '';
              @endphp
              <tr>
                <td>{{ optional($l->date_filed)->format('Y-m-d') }}</td>
                <td>{{ $l->leaveType->name ?? '-' }}</td>
                <td>
                  {{ optional($l->start_date)->format('Y-m-d') }} to
                  {{ optional($l->end_date)->format('Y-m-d') }}
                </td>
                <td>
                  <span class="badge bg-{{ $badge }} {{ $text }}">{{ strtoupper($status) }}</span>
                </td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('employee.leaves.show', $l->id) }}">
                    View
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

</div>

{{-- FullCalendar CDN --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const el = document.getElementById('calendar');
  const statusFilter = document.getElementById('statusFilter');

  const calendar = new FullCalendar.Calendar(el, {
    initialView: 'dayGridMonth',
    height: 'auto',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,listMonth'
    },

    events: function(fetchInfo, successCallback, failureCallback) {
      const params = new URLSearchParams({
        start: fetchInfo.startStr,
        end: fetchInfo.endStr,
      });

      if (statusFilter.value) params.set('status', statusFilter.value);

      fetch("{{ route('employee.dashboard.events') }}?" + params.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(res => res.json())
        .then(data => successCallback(data))
        .catch(err => failureCallback(err));
    },

    eventDisplay: 'block',
    dayMaxEvents: true,
  });

  calendar.render();

  statusFilter.addEventListener('change', function () {
    calendar.refetchEvents();
  });
});
</script>
@endsection

