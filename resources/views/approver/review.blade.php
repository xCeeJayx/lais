@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Review Leave Application</h3>
            <div class="text-muted">Application #{{ $leave->id }} - {{ $leave->employee->user->first_name }} {{ $leave->employee->user->last_name }}</div>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary"
                href="{{ route('approver.leaves.form6.pdf', $leave->id) }}"
                target="_blank">
                <i class="bi bi-printer me-1"></i> Form Preview
            </a>
            <a href="{{ route('approver.inbox') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Inbox
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- LEFT COLUMN: Details & Action --}}
        <div class="col-lg-8">

            {{-- Application Details --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between">
                    <span><i class="bi bi-file-text me-2 text-primary"></i> Application Details</span>
                    <span class="badge {{ $leave->status === 'approved' ? 'bg-success' : ($leave->status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">
                        {{ strtoupper($leave->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Applicant</div>
                        <div class="col-md-8 fw-semibold">{{ $leave->employee->user->first_name }} {{ $leave->employee->user->last_name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Position & Division</div>
                        <div class="col-md-8">{{ $leave->employee->position_title }} &bull; {{ $leave->employee->division->name ?? 'N/A' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Leave Type</div>
                        <div class="col-md-8 fw-bold text-primary">{{ $leave->leaveType->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Days Requested</div>
                        <div class="col-md-8">
                            <span class="badge bg-secondary fs-6">{{ $leave->working_days_requested }} Day(s)</span>
                        </div>
                    </div>

                    {{-- VISUAL INLINE CALENDAR FOR DATES --}}
                    @if($leave->getDetail('selected_dates') && is_array($leave->getDetail('selected_dates')))
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Selected Dates</div>
                        <div class="col-md-8">
                            <input type="text" id="selectedDatesVisual" class="d-none">
                        </div>
                    </div>
                    @endif

                    {{-- DYNAMIC CS FORM 6 DETAILS --}}
                    <div class="row mb-3">
                        <div class="col-md-4 text-muted small">Details of Leave (6.B)</div>
                        <div class="col-md-8">
                            @if($leave->isType('VL') || $leave->isType('SPL'))
                                @if($leave->getDetail('abroad'))
                                    <span class="fw-semibold">Abroad:</span> {{ $leave->getDetail('location') }}
                                @else
                                    <span class="fw-semibold">Within the Philippines</span>
                                @endif
                            @elseif($leave->isType('SL'))
                                @if($leave->getDetail('no_consultation'))
                                    <span class="fw-semibold">Out Patient:</span> {{ $leave->getDetail('reason') ?? 'N/A' }}
                                @else
                                    <span class="fw-semibold">In Hospital:</span> {{ $leave->getDetail('reason') ?? 'N/A' }}
                                @endif
                            @elseif($leave->isType('WOMEN'))
                                <span class="fw-semibold">Illness/Surgery:</span> {{ $leave->getDetail('reason') ?? 'N/A' }}
                            @elseif($leave->isType('STUDY'))
                                <span class="fw-semibold">Purpose:</span>
                                {{ $leave->getDetail('study_type') === 'bar' ? "BAR/Board Examination Review" : "Completion of Master's Degree" }}
                            @elseif($leave->isType('ML'))
                                <div class="mb-1">
                                    <span class="fw-semibold">Expected Date of Delivery (EDD):</span>
                                    {{ $leave->getDetail('edd') ? \Carbon\Carbon::parse($leave->getDetail('edd'))->format('M d, Y') : 'N/A' }}
                                </div>
                                <div>
                                    <span class="fw-semibold">Need allocation (CS Form 6a)?</span>
                                    {{ $leave->getDetail('allocation') ? 'Yes' : 'No' }}
                                </div>
                            @else
                                <span class="text-muted">Additional Details</span>
                            @endif

                            {{-- Catch any other dynamic form details just in case --}}
                            @php
                                // We added 'selected_dates' here so it doesn't double print
                                $excludeKeys = [
                                    'vl_earned', 'vl_less', 'vl_balance',
                                    'sl_earned', 'sl_less', 'sl_balance',
                                    'credits_as_of', 'abroad', 'location',
                                    'no_consultation', 'reason', 'study_type',
                                    'edd', 'allocation', 'selected_dates'
                                ];
                                $extraDetails = collect($leave->details_json ?? $leave->details ?? [])->except($excludeKeys)->filter(function($val) {
                                    return $val !== null && $val !== '';
                                });
                            @endphp

                            @if($extraDetails->isNotEmpty())
                                <ul class="mb-0 ps-3 mt-2">
                                    @foreach($extraDetails as $key => $val)
                                        <li>
                                            <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                                            @if(is_array($val))
                                                {{ implode(', ', $val) }}
                                            @else
                                                {{ is_bool($val) ? ($val ? 'Yes' : 'No') : $val }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4 text-muted small">Commutation (6.D)</div>
                        <div class="col-md-8 fw-semibold">
                            {{ $leave->commutation === 'requested' ? 'Requested' : 'Not Requested' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- 7.A CERTIFICATION OF LEAVE CREDITS (For Chief Personnel Only) --}}
            @if(auth()->user()->hasRole('approver_chief_personnel'))
                <div class="card shadow-sm mb-4 border-primary">
                    <div class="card-header bg-primary text-white fw-bold">
                        <i class="bi bi-file-earmark-check me-2"></i> 7.A CERTIFICATION OF LEAVE CREDITS
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Please fill up the employee's leave credits. This will reflect directly on the final CS Form 6.</p>

                        {{-- The Action Form must wrap this so it submits to the backend --}}
                        <form action="{{ route('approver.leaves.action', $leave->id) }}" method="POST" id="actionForm">
                            @csrf
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">As of (Month/Year)</label>
                                    <input type="text" name="credits_as_of" class="form-control" value="{{ $leave->getDetail('credits_as_of') ?? now()->format('F Y') }}" placeholder="e.g., February 2026">
                                </div>
                            </div>

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start">Leave Type</th>
                                            <th>Total Earned</th>
                                            <th>Less this application</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-start fw-semibold">Vacation Leave</td>
                                            <td><input type="number" step="0.01" name="vl_earned" id="vl_earned" class="form-control text-center" placeholder="0.00" value="{{ $leave->getDetail('vl_earned') }}" oninput="calculateVL()"></td>
                                            <td><input type="number" step="0.01" name="vl_less" id="vl_less" class="form-control text-center" value="{{ $leave->getDetail('vl_less') ?? ($leave->isType('VL') ? $leave->working_days_requested : '0.00') }}"></td>
                                            <td><input type="number" step="0.01" name="vl_balance" id="vl_balance" class="form-control text-center bg-light" placeholder="0.00" value="{{ $leave->getDetail('vl_balance') }}" readonly tabindex="-1"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-start fw-semibold">Sick Leave</td>
                                            <td><input type="number" step="0.01" name="sl_earned" id="sl_earned" class="form-control text-center" placeholder="0.00" value="{{ $leave->getDetail('sl_earned') }}" oninput="calculateSL()"></td>
                                            <td><input type="number" step="0.01" name="sl_less" id="sl_less" class="form-control text-center" value="{{ $leave->getDetail('sl_less') ?? ($leave->isType('SL') ? $leave->working_days_requested : '0.00') }}"></td>
                                            <td><input type="number" step="0.01" name="sl_balance" id="sl_balance" class="form-control text-center bg-light" placeholder="0.00" value="{{ $leave->getDetail('sl_balance') }}" readonly tabindex="-1"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <script>
                                function calculateVL() {
                                    let earned = parseFloat(document.getElementById('vl_earned').value) || 0;
                                    let less = parseFloat(document.getElementById('vl_less').value) || 0;
                                    document.getElementById('vl_balance').value = (earned - less).toFixed(2);
                                }
                                function calculateSL() {
                                    let earned = parseFloat(document.getElementById('sl_earned').value) || 0;
                                    let less = parseFloat(document.getElementById('sl_less').value) || 0;
                                    document.getElementById('sl_balance').value = (earned - less).toFixed(2);
                                }
                                document.addEventListener('DOMContentLoaded', function() {
                                    calculateVL();
                                    calculateSL();

                                    // Add listeners to 'less' fields in case they manually override it
                                    document.getElementById('vl_less').addEventListener('input', calculateVL);
                                    document.getElementById('sl_less').addEventListener('input', calculateSL);
                                });
                            </script>
            @endif

            {{-- ACTION BOX --}}
            @if($canAction)
                <div class="card shadow-sm border-0 bg-light">
                    <div class="card-body">
                        <h5 class="mb-3 fw-bold"><i class="bi bi-shield-check text-success me-2"></i> Your Action Required</h5>

                        {{-- If not Chief Personnel, we need to open the form tag here --}}
                        @if(!auth()->user()->hasRole('approver_chief_personnel'))
                            <form action="{{ route('approver.leaves.action', $leave->id) }}" method="POST">
                                @csrf
                        @endif

                            {{-- HIDE Remarks if Chief Personnel --}}
                            @if(!auth()->user()->hasRole('approver_chief_personnel'))
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Remarks / Comments (Required if Returning/Disapproving)</label>
                                    <textarea name="remarks" class="form-control" rows="3" placeholder="Enter your remarks here..."></textarea>
                                </div>
                            @else
                                {{-- Hidden remarks to bypass controller validation if they Return/Disapprove --}}
                                <input type="hidden" name="remarks" value="Processed by Chief Personnel">
                            @endif

                            <div class="d-flex gap-2">
                                <button type="submit" name="action" value="approved" class="btn btn-success px-4 fw-bold">
                                    <i class="bi bi-check-circle me-1"></i> Approve
                                </button>
                                <button type="submit" name="action" value="returned" class="btn btn-warning px-4 fw-bold">
                                    <i class="bi bi-arrow-return-left me-1"></i> Return
                                </button>
                                <button type="submit" name="action" value="disapproved" class="btn btn-danger px-4 fw-bold">
                                    <i class="bi bi-x-circle me-1"></i> Disapprove
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @elseif($leave->status === 'pending')
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> Waiting for another approver to process this step.
                </div>
            @endif

            {{-- Close the Chief Personnel card div if opened --}}
            @if(auth()->user()->hasRole('approver_chief_personnel'))
                    </div>
                </div>
            @endif
        </div>

        {{-- RIGHT COLUMN: Employee Details, Attachments & Timeline --}}
        <div class="col-lg-4">

            {{-- Applicant's Leave Details & History --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-person-lines-fill me-2 text-primary"></i> Employee Leave Details
                </div>
                <div class="card-body">
                    {{-- Current Balances --}}
                @if(auth()->user()->hasRole('approver_ard_ms'))
                    <h6 class="fw-semibold mb-3 border-bottom pb-2">Current Leave Balances</h6>
                    @if($credits)
                        <div class="row text-center mb-4">
                            <div class="col-6 border-end">
                                <div class="text-muted small">Vacation Leave</div>
                                <div class="fs-5 fw-bold text-primary">{{ number_format($credits->vacation_leave ?? 0, 2) }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Sick Leave</div>
                                <div class="fs-5 fw-bold text-primary">{{ number_format($credits->sick_leave ?? 0, 2) }}</div>
                            </div>
                        </div>
                    @else
                        <div class="text-muted small text-center mb-4">No leave credits recorded in system.</div>
                    @endif
                @endif

                    {{-- Recent History --}}
                    <h6 class="fw-semibold mb-3 border-bottom pb-2">Recent Leave History</h6>
                    @if($history->count() > 0)
                        <ul class="list-unstyled mb-0 small">
                            @foreach($history as $hist)
                                <li class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold">{{ $hist->leaveType->name ?? 'Leave' }}</span>
                                        <span class="badge {{ $hist->status === 'approved' ? 'bg-success' : ($hist->status === 'pending' ? 'bg-warning text-dark': ($hist->status === 'returned' ? 'bg-info text-dark' : 'bg-danger')) }}" style="font-size: 0.65rem;">
                                            {{ strtoupper($hist->status) }}
                                        </span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ \Carbon\Carbon::parse($hist->start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($hist->end_date)->format('M d, Y') }}
                                        ({{ $hist->working_days_requested }} days)
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-muted small text-center">No recent leave applications.</div>
                    @endif
                </div>
            </div>

            {{-- Attachments with Preview Button --}}
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-paperclip me-2 text-primary"></i> Attachments
                </div>
                <div class="card-body">
                    @if($leave->attachments->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($leave->attachments as $att)
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div class="text-truncate me-2">
                                        <i class="bi bi-file-earmark me-2 text-muted"></i>
                                        <span title="{{ $att->original_name }}" class="small">{{ $att->original_name }}</span>
                                    </div>
                                    <div class="btn-group">
                                        <a href="{{ route('attachments.preview', $att->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Preview">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('attachments.download', $att->id) }}" class="btn btn-sm btn-outline-primary" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-muted small text-center py-3">No attachments provided.</div>
                    @endif
                </div>
            </div>

            {{-- Approval Timeline --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-clock-history me-2 text-primary"></i> Approval Timeline
                </div>
                <div class="card-body">
                    <div class="position-relative ps-4 ms-2" style="border-left: 2px solid #e9ecef;">
                        @foreach($timeline as $t)
                            <div class="mb-4 position-relative">
                                {{-- Status Dot --}}
                                @php
                                    $dotColor = match($t['state']) {
                                        'approved' => 'bg-success',
                                        'returned' => 'bg-warning',
                                        'disapproved' => 'bg-danger',
                                        'current' => 'bg-primary border border-2 border-white shadow',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="position-absolute top-0 start-0 translate-middle p-2 rounded-circle {{ $dotColor }}" style="left: -17px !important;"></span>

                                <div class="fw-bold">{{ $t['title'] }}</div>

                                @if($t['state'] === 'upcoming')
                                    <div class="text-muted small">Pending...</div>
                                @elseif($t['state'] === 'current')
                                    <div class="text-primary small fw-semibold">Currently Reviewing</div>
                                @else
                                    <div class="small fw-semibold text-{{ $t['state'] === 'approved' ? 'success' : ($t['state'] === 'returned' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($t['state']) }} by {{ $t['actor'] }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ \Carbon\Carbon::parse($t['acted_at'])->format('M d, Y h:i A') }}
                                    </div>
                                    @if($t['remarks'] && $t['remarks'] !== 'Processed by Chief Personnel')
                                        <div class="mt-1 p-2 bg-light rounded small border-start border-3 border-secondary">
                                            "{{ $t['remarks'] }}"
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
{{-- Flatpickr CSS & JS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<style>
  /* Optional CSS to make the inline calendar look cleaner */
  .flatpickr-calendar.inline {
      box-shadow: none !important;
      border: 1px solid #dee2e6;
      margin-top: 0.25rem;
  }
</style>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    @if($leave->getDetail('selected_dates') && is_array($leave->getDetail('selected_dates')))
        const originalDates = {!! json_encode($leave->getDetail('selected_dates')) !!};

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

@endsection
