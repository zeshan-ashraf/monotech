@extends('admin.layout.app')
@section('title', 'Carrier Unavailable')
@push('css')
<style>
    .carrier-down-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 12rem);
        padding: 2rem 1rem;
    }

    .carrier-down-card {
        width: 100%;
        max-width: 560px;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        background: #fff;
    }

    .carrier-down-header {
        background: linear-gradient(135deg, #f97316 0%, #ef4444 55%, #e85d4c 100%);
        padding: 2.5rem 2rem 2rem;
        text-align: center;
        color: #fff;
    }

    .carrier-down-icon {
        width: 72px;
        height: 72px;
        margin: 0 auto 1.25rem;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .carrier-down-icon svg,
    .carrier-down-btn-primary svg,
    .carrier-down-btn-secondary svg {
        flex-shrink: 0;
    }

    .carrier-down-header h1 {
        font-size: 1.65rem;
        font-weight: 700;
        margin: 0;
        color: #fff;
    }

    .carrier-down-body {
        padding: 2rem;
        text-align: center;
    }

    .carrier-down-badge {
        display: inline-block;
        background: #fce4e4;
        color: #dc2626;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        padding: 0.35rem 0.85rem;
        border-radius: 6px;
        margin-bottom: 1.25rem;
    }

    .carrier-down-message {
        color: #6b7280;
        font-size: 0.95rem;
        line-height: 1.6;
        margin: 0 0 1.5rem;
    }

    .carrier-down-ref {
        background: #f3f4f6;
        border-radius: 8px;
        padding: 0.85rem 1rem;
        font-size: 0.9rem;
        color: #374151;
        margin-bottom: 1.75rem;
        word-break: break-all;
    }

    .carrier-down-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .carrier-down-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #7367f0;
        color: #fff !important;
        border: none;
        padding: 0.65rem 1.35rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: background 0.2s;
    }

    .carrier-down-btn-primary:hover {
        background: #5e50ee;
        color: #fff;
    }

    .carrier-down-btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #fff;
        color: #6b7280 !important;
        border: 1px solid #d1d5db;
        padding: 0.65rem 1.35rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: border-color 0.2s, color 0.2s;
    }

    .carrier-down-btn-secondary:hover {
        border-color: #9ca3af;
        color: #374151 !important;
    }
</style>
@endpush
@section('content')
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="content-wrapper container-xxl p-0">
        <div class="content-body">
            <div class="carrier-down-wrap">
                <div class="carrier-down-card">
                    <div class="carrier-down-header">
                        <div class="carrier-down-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                                <line x1="3" y1="3" x2="21" y2="21"/>
                            </svg>
                        </div>
                        <h1>Carrier Is Down Right Now</h1>
                    </div>
                    <div class="carrier-down-body">
                        <span class="carrier-down-badge">status inquiry is temporarily unavailable</span>
                        <p class="carrier-down-message">{{ $carrierMessage }}</p>
                        <div class="carrier-down-ref">Ref: {{ $referenceId }}</div>
                        <div class="carrier-down-actions">
                            <a href="{{ $retryUrl }}" class="carrier-down-btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                    <path d="M3 3v5h5"/>
                                    <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                                    <path d="M16 16h5v5"/>
                                </svg>
                                Try Again
                            </a>
                            <a href="{{ $backUrl }}" class="carrier-down-btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m12 19-7-7 7-7"/>
                                    <path d="M19 12H5"/>
                                </svg>
                                Go Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
