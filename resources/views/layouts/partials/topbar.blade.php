@php
  $user = Auth::user();
@endphp

{{--
    UPDATED: Added 'sticky-top', 'bg-white', and 'shadow-sm'
    - sticky-top: Makes it stay at the top when scrolling
    - bg-white: Ensures it has a solid background
    - shadow-sm: Adds a subtle shadow for better separation
--}}
<nav class="navbar lais-topbar navbar-expand-lg sticky-top bg-white shadow-sm">
    <div class="container-fluid">

        <button class="btn btn-sm lais-icon-btn d-lg-none me-2"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#laisSidebar">
            <i class="bi bi-list"></i>
        </button>

        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/denr_logo.png') }}"
                 alt="DENR Logo"
                 style="height: 40px; width: auto;">

            <div class="d-flex flex-column" style="line-height: 1;">
                <span class="fw-bold" style="font-size: 1.1rem;">DENR</span>
            </div>
            <span class="fw-bold badge text-bg-light border" style="font-size: 1rem;">LAIS</span>
        </a>

        <div class="ms-auto d-flex align-items-center gap-2">

            <div class="dropdown">
                <button class="btn btn-sm lais-user-btn dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i> {{ $user->first_name }}
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li class="px-3 py-2">
                        <div class="fw-semibold">{{ $user->name }}</div>
                        <div class="text-muted small">{{ $user->email }}</div>
                    </li>
                    <li><hr class="dropdown-divider"></li>

                    @if($user->hasRole('employee'))
                        <a class="dropdown-item" href="{{ route('employee.profile.show') }}">
                            <i class="bi bi-person-badge me-2"></i> My Profile
                        </a>
                        <li><hr class="dropdown-divider"></li>
                    @endif

                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-start lais-offcanvas" tabindex="-1" id="laisSidebar">
  <div class="offcanvas-header">
    <div class="d-flex align-items-center gap-2">
      <img src="{{ asset('images/denr_logo.png') }}"
           alt="DENR Logo"
           style="height: 40px; width: auto;">
      <div>
        <div class="fw-bold">LAIS</div>
        <div class="text-muted small">Navigation</div>
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    @include('layouts.partials.sidebar')
  </div>
</div>
