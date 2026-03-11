@extends('layouts.app')

@section('content')
<div class="container py-4">
  @php
      $roleMap = \App\Models\Role::pluck('name', 'key')->toArray();
  @endphp

  {{-- Header --}}
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h3 class="mb-0">Leave Application Details</h3>
      <div class="text-muted small">
        Reference ID: <span class="fw-semibold">#{{ $leave->id }}</span>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('employee.leaves.index') }}">
        <i class="bi bi-arrow-left"></i> Back to History
      </a>
      @if(in_array($leave->status, ['pending','approved','disapproved','returned'], true))
        <a class="btn btn-outline-primary"
            href="{{ route('employee.leaves.form6.pdf', $leave->id) }}"
            target="_blank">
            <i class="bi bi-printer me-1"></i> Print Form 6
        </a>
      @endif
    </div>
  </div>

  {{-- Summary cards --}}
  <div class="row g-3 mb-3">
    <div class="col-lg-3 col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="text-muted small">Status</div>
          @php
            $badge = match($leave->status) {
              'approved' => 'success',
              'pending' => 'warning',
              'returned' => 'info',
              'disapproved' => 'danger',
              default => 'secondary'
            };
          @endphp
          <div class="d-flex align-items-center justify-content-between mt-1">
            <div class="fs-5 fw-semibold text-capitalize">{{ $leave->status }}</div>
            <span class="badge text-bg-{{ $badge }}">{{ strtoupper($leave->status) }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Leave Type</div>
          <div class="fs-5 fw-semibold">{{ $leave->leaveType->name ?? '—' }}</div>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="text-muted small">Date Filed</div>
          <div class="fs-5 fw-semibold">
            {{ optional($leave->date_filed)->format('Y-m-d') ?? optional($leave->created_at)->format('Y-m-d') }}
          </div>
          <div class="text-muted small mt-1">
            {{ optional($leave->created_at)->format('h:i A') }}
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="text-muted small">Working Days</div>
          <div class="fs-5 fw-semibold">{{ $leave->working_days_requested ?? '—' }}</div>
          <div class="text-muted small mt-1">Commutation: {{ $leave->commutation ?? '—' }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Main content --}}
  <div class="row g-3 align-items-start">

    {{-- LEFT COLUMN --}}
    <div class="col-lg-7">

      {{-- Applicant Information --}}
      <div class="card mb-3">
        <div class="card-header bg-white fw-semibold">
          <i class="bi bi-person-badge me-1"></i> Applicant Information
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="text-muted small">Employee Name</div>
              <div class="fw-semibold">
                {{ $leave->employee->user->name ?? $leave->employee->full_name ?? '—' }}
              </div>
              <div class="text-muted small">{{ $leave->employee->user->email ?? '' }}</div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Office</div>
              <div class="fw-semibold">
                {{ $leave->office->name ?? '—' }}
              </div>
              @if(!empty($leave->office->office_code))
                <div class="text-muted small">Code: {{ $leave->office->office_code }}</div>
              @endif
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Division</div>
              <div class="fw-semibold">
                {{ $leave->employee->division->name ?? '—' }}
              </div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Position/Designation</div>
              <div class="fw-semibold">{{ $leave->employee->position_title ?? '—' }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Application Information --}}
      <div class="card mb-3">
        <div class="card-header bg-white fw-semibold">
          <i class="bi bi-file-text me-1"></i> Application Information
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="text-muted small">Days Requested</div>
              <div class="fw-semibold">{{ $leave->working_days_requested ?? '—' }}</div>
            </div>

            <div class="col-md-6">
              <div class="text-muted small">Progress</div>
              <div class="fw-semibold">Step {{ $leave->current_step_order ?? '—' }}</div>
            </div>
          </div>

          @php
            $details = $leave->details_json ?? [];
            if (is_string($details)) {
              $decoded = json_decode($details, true);
              $details = is_array($decoded) ? $decoded : [];
            }
          @endphp

          <hr class="my-3">

          {{-- 2-COLUMN LAYOUT: Details (Left) + Calendar (Right) --}}
          <div class="row g-4">

            {{-- Additional Details Column --}}
            <div class="col-md-6">
              <div class="fw-semibold mb-3">Additional Details</div>
              <div class="row g-2">
                <div class="col-sm-6">
                  <div class="p-2 border rounded bg-light h-100">
                    <div class="text-muted small">Abroad</div>
                    <div class="fw-semibold">{{ !empty($details['abroad']) ? 'Yes' : 'No' }}</div>
                  </div>
                </div>

                <div class="col-sm-6">
                  <div class="p-2 border rounded bg-light h-100">
                    <div class="text-muted small">No Consultation</div>
                    <div class="fw-semibold">{{ !empty($details['no_consultation']) ? 'Yes' : 'No' }}</div>
                  </div>
                </div>

                @if(!empty($details['reason']))
                  <div class="col-12">
                    <div class="p-2 border rounded bg-light">
                      <div class="text-muted small">Reason</div>
                      <div class="fw-semibold">{{ $details['reason'] }}</div>
                    </div>
                  </div>
                @endif

                @if(!empty($details['location']))
                  <div class="col-12">
                    <div class="p-2 border rounded bg-light">
                      <div class="text-muted small">Location</div>
                      <div class="fw-semibold">{{ $details['location'] }}</div>
                    </div>
                  </div>
                @endif
              </div>
            </div>

            {{-- Selected Dates Calendar Column --}}
            @if(!empty($details['selected_dates']) && is_array($details['selected_dates']))
              <div class="col-md-6">
                <div class="fw-semibold mb-3">Specific Dates Selected</div>
                <div class="d-flex justify-content-center p-2 border rounded bg-light">
                  <input type="text" id="selectedDatesVisual" class="d-none">
                </div>
              </div>
            @endif

          </div>

        </div>
      </div>

    </div> {{-- ✅ END LEFT COLUMN --}}

    {{-- RIGHT COLUMN --}}
    <div class="col-lg-5">

      {{-- Attachments --}}
      <div class="card mb-3">
        <div class="card-header bg-white fw-semibold">
          <i class="bi bi-paperclip me-1"></i> Attachments
        </div>
        <div class="card-body">
          @if(!isset($leave->attachments) || $leave->attachments->count() === 0)
            <div class="text-muted">No attachments uploaded.</div>
          @else
            <div class="list-group">
              @foreach($leave->attachments as $a)
                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="fw-semibold">{{ $a->original_name }}</div>
                      <div class="text-muted small">
                        {{ $a->mime_type }} • {{ number_format(($a->size ?? 0) / 1024, 1) }} KB
                      </div>
                    </div>
                    <div class="d-flex gap-2">
                      <a class="btn btn-sm btn-outline-primary"
                         href="{{ route('attachments.preview', $a->id) }}"
                         target="_blank">Preview</a>
                      <a class="btn btn-sm btn-outline-secondary"
                         href="{{ route('attachments.download', $a->id) }}">Download</a>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
        <div class="card-footer bg-white text-muted small">
          Tip: Use <span class="fw-semibold">Preview</span> to view in the browser.
          Use <span class="fw-semibold">Download</span> only if you need a copy.
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header bg-white fw-semibold">
          <i class="bi bi-diagram-3 me-1"></i> Approval Progress
        </div>
        <div class="card-body">

          @if(empty($timeline) || $timeline->count() === 0)
            <div class="text-muted">No approval steps configured for this office.</div>
          @else
            <div class="list-group">
              @foreach($timeline as $t)
                @php
                  $displayRole = $roleMap[$t['role_key']] ?? $t['title'] ?? $t['role_key'];

                  $state = $t['state'];
                  $badge = match($state) {
                    'approved' => 'success',
                    'returned' => 'info',
                    'disapproved' => 'danger',
                    'current' => 'warning',
                    'upcoming' => 'secondary',
                    default => 'secondary',
                  };
                  $label = match($state) {
                    'approved' => 'APPROVED',
                    'returned' => 'RETURNED',
                    'disapproved' => 'DISAPPROVED',
                    'current' => 'PENDING (CURRENT)',
                    'upcoming' => 'UPCOMING',
                    default => strtoupper($state),
                  };
                @endphp

                <div class="list-group-item">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="fw-semibold">(Step {{ $t['step_order'] }})  {{ $displayRole }}</div>

                      {{-- Optional: Show who actually acted on it if available --}}
                      @if(!empty($t['actor']))
                        <div class="text-muted small">By: {{ $t['actor'] }}</div>
                      @endif
                    </div>
                    <span class="badge text-bg-{{ $badge }}">{{ $label }}</span>
                  </div>

                  @if(!empty($t['remarks']))
                    <div class="mt-2">
                      <div class="text-muted small">Remarks</div>
                      <div>{{ $t['remarks'] }}</div>
                    </div>
                  @endif

                  @if(!empty($t['acted_at']))
                    <div class="text-muted small mt-2">
                      {{ \Carbon\Carbon::parse($t['acted_at'])->format('Y-m-d h:i A') }}
                    </div>
                  @endif
                </div>
              @endforeach
            </div>
          @endif

        </div>
      </div>

    </div> {{-- ✅ END RIGHT COLUMN --}}

  </div> {{-- ✅ END ROW --}}

</div>
@endsection

@push('scripts')
{{-- Flatpickr CSS & JS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
  /* Optional CSS to make the inline calendar look cleaner */
  .flatpickr-calendar.inline {
      box-shadow: none !important;
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
  }
</style>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    @if(!empty($details['selected_dates']) && is_array($details['selected_dates']))
        const originalDates = {!! json_encode($details['selected_dates']) !!};

        flatpickr("#selectedDatesVisual", {
            inline: true,
            mode: "multiple",
            defaultDate: originalDates,
            // Automatically locks the dates so they can't be added or removed visually
            onChange: function(selectedDates, dateStr, instance) {
                instance.setDate(originalDates);
            }
        });
    @endif
  });
</script>
@endpush
