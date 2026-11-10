@extends('gym::layouts.scanner')
@section('title', __('gym::lang.attendance_scanner'))
@section('content')
    <style>
        /* Reset base styles to avoid conflicts */
        .scanner-page * {
            box-sizing: border-box;
        }

        .scanner-page {
            /* min-height: 100vh;
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            padding: 1.5rem 1rem; */
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .scanner-container {
            max-width: 500px;
            margin: 0 auto;
        }

        /* Header styling */
        .scanner-header {
            text-align: center;
            margin-bottom: 2rem;
            color: white;
        }

        .scanner-icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
        }

        .scanner-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }

        .scanner-subtitle {
            font-size: 0.95rem;
            opacity: 0.9;
            margin: 0;
        }

        /* Scanner card */
        .scanner-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        #reader {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            position: relative;
            min-height: 280px;
        }

        #reader video {
            width: 100% !important;
            height: auto !important;
            max-height: 65vh !important;
            object-fit: contain !important;
        }

        /* USB Scanner Mode */
        .usb-scanner-container {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 3px dashed #667eea;
            position: relative;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .usb-scanner-container.active {
            border-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            animation: pulse-border 2s ease-in-out infinite;
        }

        .usb-scanner-container.scanning {
            border-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }

        @keyframes pulse-border {

            0%,
            100% {
                border-color: #10b981;
            }

            50% {
                border-color: #34d399;
            }
        }

        .usb-scanner-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .usb-scanner-icon i {
            font-size: 2rem;
            color: white;
        }

        .usb-scanner-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #374151;
            margin: 0 0 0.5rem 0;
            text-align: center;
        }

        .usb-scanner-subtitle {
            font-size: 0.9rem;
            color: #6b7280;
            margin: 0 0 1.5rem 0;
            text-align: center;
        }

        #usb_barcode_input {
            width: 100%;
            max-width: 300px;
            padding: 1rem 1.25rem;
            font-size: 1.25rem;
            font-weight: 600;
            text-align: center;
            border: 3px solid #667eea;
            border-radius: 12px;
            background: white;
            color: #1f2937;
            outline: none;
            transition: all 0.3s ease;
            letter-spacing: 2px;
        }

        #usb_barcode_input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
        }

        #usb_barcode_input::placeholder {
            color: #9ca3af;
            font-weight: 400;
            letter-spacing: normal;
        }

        .usb-scanner-status {
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .usb-scanner-status.ready {
            background: #d1fae5;
            color: #065f46;
        }

        .usb-scanner-status.waiting {
            background: #fef3c7;
            color: #92400e;
        }

        .usb-scanner-status i {
            animation: blink 1.5s ease-in-out infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }

        /* Mode Toggle */
        .mode-toggle-container {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding: 0.25rem;
            background: #f3f4f6;
            border-radius: 12px;
        }

        .mode-toggle-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: transparent;
            color: #6b7280;
        }

        .mode-toggle-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .mode-toggle-btn:hover:not(.active) {
            background: #e5e7eb;
            color: #374151;
        }

        /* Tips section */
        .scanner-tips {
            display: flex;
            justify-content: space-around;
            margin-top: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .tip-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.75rem;
            color: #6c757d;
            text-align: center;
        }

        .tip-item i {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
            color: #667eea;
        }

        /* Control buttons */
        .scanner-controls {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .btn-scanner {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-start {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-stop {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-stop:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
        }

        #camera_selection {
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
        }

        /* Result card */
        .result-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            padding: 1.5rem;
            border: 3px solid #e9ecef;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result-card.success-checkin {
            border-color: #10b981;
            background: linear-gradient(to bottom, #ffffff 0%, #ecfdf5 100%);
        }

        .result-card.success-checkout {
            border-color: #f59e0b;
            background: linear-gradient(to bottom, #ffffff 0%, #fffbeb 100%);
        }

        .result-card.error {
            border-color: #ef4444;
            background: linear-gradient(to bottom, #ffffff 0%, #fef2f2 100%);
        }

        .result-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .result-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            flex-shrink: 0;
        }

        .result-icon.success-checkin {
            background: #d1fae5;
            color: #10b981;
        }

        .result-icon.success-checkout {
            background: #fef3c7;
            color: #f59e0b;
        }

        .result-icon.error {
            background: #fee2e2;
            color: #ef4444;
        }

        .result-content {
            flex: 1;
        }

        .member-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 0.5rem 0;
        }

        .action-text {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }

        .time-text {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0;
        }

        .session-info {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.03);
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .session-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .session-info-item:last-child {
            margin-bottom: 0;
        }

        .session-info-item i {
            width: 16px;
            color: #667eea;
        }

        .btn-scan-again {
            width: 100%;
            margin-top: 1rem;
            padding: 0.875rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-scan-again:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Loading state */
        .loading-state {
            text-align: center;
            color: white;
            margin-top: 1rem;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .hidden {
            display: none !important;
        }

        /* Mobile optimization */
        @media (max-width: 640px) {
            .scanner-page {
                padding: 1rem 0.75rem;
            }

            .scanner-title {
                font-size: 1.5rem;
            }

            .scanner-tips {
                flex-direction: column;
                gap: 0.5rem;
            }

            .tip-item {
                flex-direction: row;
                justify-content: center;
                gap: 0.5rem;
            }
        }

        @media (min-width: 768px) {
            #reader {
                min-height: 360px;
            }
        }
    </style>

    <div class="scanner-page">
        <div class="scanner-container">
            <!-- Header -->
            <header class="scanner-header">
                <div class="scanner-icon-wrap">
                    <i class="fa fa-qrcode" style="font-size: 1.75rem;"></i>
                </div>
                <h1 class="scanner-title">{{ __('gym::lang.attendance_scanner') }}</h1>
                <p class="scanner-subtitle">{{ __('gym::lang.scan_to_checkin_checkout') }}</p>
            </header>

            <!-- Scanner Card -->
            <div class="scanner-card">
                <!-- Mode Toggle -->
                <div class="mode-toggle-container">
                    <button type="button" class="mode-toggle-btn active" data-mode="camera" id="mode_camera_btn">
                        <i class="fa fa-camera"></i>
                        <span>{{ __('gym::lang.camera_mode') ?? 'Camera' }}</span>
                    </button>
                    <button type="button" class="mode-toggle-btn" data-mode="usb" id="mode_usb_btn">
                        <i class="fa fa-shower"></i>
                        <span>{{ __('gym::lang.usb_scanner') ?? 'USB Scanner' }}</span>
                    </button>
                </div>

                <!-- Camera Scanner (default) -->
                <div id="camera_scanner_container">
                    <div id="reader"></div>

                    <div class="scanner-tips">
                        <div class="tip-item">
                            <i class="fa fa-lightbulb"></i>
                            <span>{{ __('gym::lang.good_lighting') ?? 'Good lighting' }}</span>
                        </div>
                        <div class="tip-item">
                            <i class="fa fa-tty"></i>
                            <span>{{ __('gym::lang.hold_steady') ?? 'Hold steady' }}</span>
                        </div>
                        <div class="tip-item">
                            <i class="fa fa-qrcode"></i>
                            <span>{{ __('gym::lang.clear_qr') ?? 'Clear QR' }}</span>
                        </div>
                    </div>

                    <div class="scanner-controls">
                        <button id="start_button" class="btn-scanner btn-start">
                            <i class="fa fa-play"></i>
                            <span>{{ __('gym::lang.start_scanner') ?? 'Start Scanner' }}</span>
                        </button>
                        <button id="stop_button" class="btn-scanner btn-stop hidden">
                            <i class="fa fa-stop"></i>
                            <span>{{ __('gym::lang.stop_scanner') ?? 'Stop Scanner' }}</span>
                        </button>
                        <select id="camera_selection" class="hidden"></select>
                    </div>
                </div>

                <!-- USB Barcode Scanner -->
                <div id="usb_scanner_container" class="hidden">
                    <div class="usb-scanner-container active" id="usb_scanner_area">
                        <div class="usb-scanner-icon">
                            <i class="fa fa-barcode"></i>
                        </div>
                        <h3 class="usb-scanner-title">{{ __('gym::lang.usb_barcode_scanner') ?? 'USB Barcode Scanner' }}
                        </h3>
                        <p class="usb-scanner-subtitle">
                            {{ __('gym::lang.scan_barcode_with_usb') ?? 'Scan barcode using your USB 2D scanner device' }}
                        </p>

                        <input type="text" id="usb_barcode_input"
                            placeholder="{{ __('gym::lang.waiting_for_scan') ?? 'Waiting for scan...' }}" autocomplete="off"
                            autofocus>

                        <div class="usb-scanner-status ready" id="usb_scanner_status">
                            <i class="fa fa-circle"></i>
                            <span>{{ __('gym::lang.ready_to_scan') ?? 'Ready to scan' }}</span>
                        </div>
                    </div>

                    <div class="scanner-tips" style="margin-top: 1rem;">
                        <div class="tip-item">
                            <i class="fa fa-shower"></i>
                            <span>{{ __('gym::lang.connect_usb') ?? 'Connect USB' }}</span>
                        </div>
                        <div class="tip-item">
                            <i class="fa fa-barcode"></i>
                            <span>{{ __('gym::lang.point_and_scan') ?? 'Point & Scan' }}</span>
                        </div>
                        <div class="tip-item">
                            <i class="fa fa-bolt"></i>
                            <span>{{ __('gym::lang.auto_submit') ?? 'Auto Submit' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Result Card -->
        <div id="result_container" class="hidden">
            <div id="result_card" class="result-card">
                <div class="result-header">
                    <div id="result_icon" class="result-icon"></div>
                    <div class="result-content">
                        <h2 id="member_name" class="member-name"></h2>
                        <p id="action_text" class="action-text"></p>
                        <p id="time_text" class="time-text"></p>
                    </div>
                </div>
                <div id="session_info" class="session-info hidden"></div>
                <button id="scan_again_btn" class="btn-scan-again">
                    <i class="fa fa-refresh"></i>
                    {{ __('gym::lang.scan_again') ?? 'Scan Again' }}
                </button>
            </div>
        </div>

        <!-- OTP Verification Form -->
        <div id="otp_container" class="hidden">
            <div class="result-card"
                style="border-color: #3b82f6; background: linear-gradient(to bottom, #ffffff 0%, #eff6ff 100%);">
                <div class="result-header">
                    <div class="result-icon" style="background: #dbeafe; color: #3b82f6;">
                        <i class="fa fa-envelope"></i>
                    </div>
                    <div class="result-content">
                        <h2 id="otp_member_name" class="member-name"></h2>
                        <p class="action-text" style="color: #3b82f6; font-weight: 600;">
                            {{ __('gym::lang.otp_verification_required') ?? 'OTP Verification Required' }}
                        </p>
                        <p id="otp_email_hint" class="time-text"></p>
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <p style="font-size: 14px; color: #475569; margin-bottom: 1rem;">
                        {{ __('gym::lang.enter_otp_sent_to_email') ?? 'Enter the 6-digit OTP code sent to your email' }}
                    </p>

                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                        <input type="text" id="otp_input" maxlength="6" placeholder="000000"
                            style="flex: 1; padding: 1rem; font-size: 1.5rem; font-weight: 700; text-align: center; border: 2px solid #e2e8f0; border-radius: 10px; letter-spacing: 8px; font-family: Consolas, monospace;"
                            autocomplete="off">
                    </div>

                    <div id="otp_error" class="hidden"
                        style="color: #ef4444; font-size: 13px; margin-bottom: 1rem; padding: 10px; background: #fef2f2; border-radius: 6px;">
                    </div>

                    <button id="verify_otp_btn" class="btn-scan-again"
                        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); margin-bottom: 0.75rem;">
                        <i class="fa fa-check-circle"></i>
                        {{ __('gym::lang.verify_and_checkin') ?? 'Verify & Check In' }}
                    </button>

                    <button id="resend_otp_btn"
                        style="width: 100%; padding: 0.75rem; background: transparent; border: 2px solid #e2e8f0; border-radius: 10px; font-weight: 500; font-size: 0.9rem; cursor: pointer; color: #64748b;">
                        <i class="fa fa-refresh"></i>
                        {{ __('gym::lang.resend_otp') ?? 'Resend OTP Code' }}
                    </button>

                    <button id="cancel_otp_btn"
                        style="width: 100%; margin-top: 0.75rem; padding: 0.75rem; background: #f1f5f9; border: none; border-radius: 10px; font-weight: 500; font-size: 0.9rem; cursor: pointer; color: #64748b;">
                        <i class="fa fa-arrow-left"></i>
                        {{ __('gym::lang.back_to_scanner') ?? 'Back to Scanner' }}
                    </button>
                </div>

                <input type="hidden" id="otp_member_id" value="">
            </div>
        </div>

        <!-- Loading -->
        <div id="loading_state" class="loading-state hidden">
            <div class="loading-spinner"></div>
            <p>{{ __('gym::lang.processing') ?? 'Processing...' }}</p>
        </div>
    </div>
    </div>
@endsection

@section('javascript')
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            let html5QrCode = null;
            let isScanning = false;
            let currentCameraId = null;
            let scanProcessing = false;

            // Enhanced beep sound function - more noticeable
            function playBeep(success = true) {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = ctx.createOscillator();
                    const gainNode = ctx.createGain();

                    oscillator.type = 'sine';

                    if (success) {
                        // Success: Two quick beeps
                        oscillator.frequency.setValueAtTime(880, ctx.currentTime);
                        gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);

                        oscillator.connect(gainNode);
                        gainNode.connect(ctx.destination);
                        oscillator.start(ctx.currentTime);
                        oscillator.stop(ctx.currentTime + 0.15);

                        // Second beep
                        setTimeout(() => {
                            const osc2 = ctx.createOscillator();
                            const gain2 = ctx.createGain();
                            osc2.type = 'sine';
                            osc2.frequency.setValueAtTime(1046, ctx.currentTime);
                            gain2.gain.setValueAtTime(0.3, ctx.currentTime);
                            gain2.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
                            osc2.connect(gain2);
                            gain2.connect(ctx.destination);
                            osc2.start(ctx.currentTime);
                            osc2.stop(ctx.currentTime + 0.15);
                        }, 150);
                    } else {
                        // Error: Lower, longer beep
                        oscillator.frequency.setValueAtTime(300, ctx.currentTime);
                        gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);

                        oscillator.connect(gainNode);
                        gainNode.connect(ctx.destination);
                        oscillator.start(ctx.currentTime);
                        oscillator.stop(ctx.currentTime + 0.4);
                    }
                } catch (e) {
                    console.warn('Audio not supported:', e);
                }
            }

            function showResult(data) {
                const card = $('#result_card');
                const icon = $('#result_icon');

                // Reset classes
                card.removeClass('success-checkin success-checkout error');
                icon.removeClass('success-checkin success-checkout error');

                if (data.success) {
                    playBeep(true);

                    if (data.action === 'check_in') {
                        card.addClass('success-checkin');
                        icon.addClass('success-checkin');
                        icon.html('<i class="fa fa-arrow-up"></i>');
                        $('#action_text').html('<span style="color: #10b981; font-weight: 600;">{{ __("gym::lang.checked_in") }}</span>');
                    } else {
                        card.addClass('success-checkout');
                        icon.addClass('success-checkout');
                        icon.html('<i class="fa fa-arrow-down"></i>');
                        $('#action_text').html('<span style="color: #f59e0b; font-weight: 600;">{{ __("gym::lang.checked_out") }}</span>');
                    }

                    $('#member_name').text(data.member_name || '');
                    $('#time_text').text(data.time || '');

                    let sessionHtml = '';
                    if (data.session_info) {
                        sessionHtml += '<div class="session-info-item"><i class="fa fa-clock"></i><span>' + data.session_info + '</span></div>';
                    }
                    if (data.duration) {
                        sessionHtml += '<div class="session-info-item"><i class="fa fa-hourglass-end"></i><span>{{ __("gym::lang.duration") }}: <strong>' + data.duration + '</strong></span></div>';
                    }
                    if (data.remaining_info) {
                        sessionHtml += '<div class="session-info-item"><i class="fa fa-battery-half"></i><span>' + data.remaining_info + '</span></div>';
                    }
                    if (data.package_exhausted) {
                        sessionHtml += '<div class="session-info-item" style="color: #ef4444;"><i class="fa fa-exclamation-triangle"></i><span>{{ __("gym::lang.package_exhausted") }}</span></div>';
                    }
                    if (data.has_subscription === false) {
                        sessionHtml += '<div class="session-info-item" style="color: #f59e0b;"><i class="fa fa-exclamation-circle"></i><span>{{ __("gym::lang.no_active_subscription") }}</span></div>';
                    }
                    if (data.has_subscription === true && !data.session_info && !data.remaining_info) {
                        sessionHtml += '<div class="session-info-item" style="color: #10b981;"><i class="fa fa-check-circle"></i><span>{{ __("gym::lang.unlimited") }}</span></div>';
                    }

                    if (sessionHtml) {
                        $('#session_info').html(sessionHtml).removeClass('hidden');
                    } else {
                        $('#session_info').addClass('hidden');
                    }
                } else {
                    playBeep(false);

                    card.addClass('error');
                    icon.addClass('error');
                    icon.html('<i class="fa fa-times-circle"></i>');

                    // Handle different error types
                    if (data.action === 'check_in_denied') {
                        $('#member_name').text(data.member_name || '{{ __("gym::lang.member") }}');
                        $('#action_text').html('<span style="color: #ef4444; font-weight: 600;">{{ __("gym::lang.check_in_denied") }}</span>');
                        $('#time_text').text(data.time || '');

                        // Show reason in session info
                        let errorHtml = '<div class="session-info-item" style="color: #ef4444;"><i class="fa fa-ban"></i><span>' + data.message + '</span></div>';
                        $('#session_info').html(errorHtml).removeClass('hidden');
                    } else {
                        $('#member_name').text('{{ __("gym::lang.error") ?? "Error" }}');
                        $('#action_text').html('<span style="color: #ef4444;">' + (data.message || '{{ __("gym::lang.error_processing") ?? "Error processing request" }}') + '</span>');
                        $('#time_text').text('');
                        $('#session_info').addClass('hidden');
                    }
                }

                $('#result_container').removeClass('hidden');
            }

            function setLoading(loading) {
                if (loading) {
                    $('#loading_state').removeClass('hidden');
                } else {
                    $('#loading_state').addClass('hidden');
                }
            }

            function getQrboxSize() {
                const readerEl = document.getElementById('reader');
                const readerWidth = readerEl ? readerEl.clientWidth : 400;
                const size = Math.min(250, Math.floor(readerWidth * 0.7));
                return { width: size, height: size };
            }

            async function startScanner(cameraId = null) {
                try {
                    $('#result_container').addClass('hidden');
                    setLoading(true);

                    if (isScanning) {
                        console.log('Scanner already running');
                        setLoading(false);
                        return;
                    }

                    if (!html5QrCode) {
                        html5QrCode = new Html5Qrcode("reader");
                    }

                    const config = {
                        fps: 32,
                        qrbox: getQrboxSize(),
                        aspectRatio: 1.0,
                        disableFlip: false
                    };

                    const cameraConfig = cameraId
                        ? { deviceId: { exact: cameraId } }
                        : { facingMode: "environment" };

                    await html5QrCode.start(cameraConfig, config, onScanSuccess, onScanError);

                    isScanning = true;
                    $('#start_button').addClass('hidden');
                    $('#stop_button').removeClass('hidden');

                    console.log('Scanner started successfully');
                } catch (err) {
                    console.error('Scanner start error:', err);
                    alert('{{ __("gym::lang.camera_error") ?? "Cannot access camera. Please grant camera permission." }}\n\n' + err.message);
                } finally {
                    setLoading(false);
                }
            }

            async function stopScanner() {
                try {
                    if (html5QrCode && isScanning) {
                        await html5QrCode.stop();
                        isScanning = false;
                        $('#start_button').removeClass('hidden');
                        $('#stop_button').addClass('hidden');
                        console.log('Scanner stopped');
                    }
                } catch (err) {
                    console.warn('Stop scanner error:', err);
                }
            }

            async function onScanSuccess(decodedText, decodedResult) {
                if (scanProcessing) {
                    console.log('Already processing a scan');
                    return;
                }

                scanProcessing = true;
                console.log('QR Code detected:', decodedText);

                // Play beep immediately when QR is detected
                playBeep(true);

                try {
                    await stopScanner();
                    setLoading(true);

                    $.ajax({
                        url: "{{ route('gym.public_checkinout') }}",
                        method: 'POST',
                        data: {
                            member_id: decodedText,
                            _token: '{{ csrf_token() }}'
                        },
                        dataType: 'json',
                        timeout: 10000
                    }).done(function (response) {
                        console.log('Response:', response);

                        // Handle OTP required for check-in
                        if (response.success && response.action === 'require_otp') {
                            showOtpForm(response);
                            // Auto-send OTP
                            sendOtp(response.member_id);
                        } else {
                            showResult(response);
                        }
                    }).fail(function (jqXHR, textStatus, errorThrown) {
                        console.error('AJAX error:', textStatus, errorThrown);
                        showResult({
                            success: false,
                            message: '{{ __("gym::lang.error_processing") ?? "Error processing request" }}'
                        });
                    }).always(function () {
                        setLoading(false);
                        scanProcessing = false;
                    });
                } catch (e) {
                    console.error('Processing error:', e);
                    setLoading(false);
                    scanProcessing = false;
                }
            }

            // OTP Functions
            function showOtpForm(data) {
                $('#otp_container').removeClass('hidden');
                $('#result_container').addClass('hidden');
                $('#otp_member_name').text(data.member_name);
                $('#otp_email_hint').text('{{ __("gym::lang.otp_sent_to") ?? "OTP sent to" }}: ' + data.email_hint);
                $('#otp_member_id').val(data.member_id);
                $('#otp_input').val('').focus();
                $('#otp_error').addClass('hidden');
            }

            function sendOtp(memberId) {
                setLoading(true);

                $.ajax({
                    url: "{{ route('gym.public_send_otp') }}",
                    method: 'POST',
                    data: {
                        member_id: memberId,
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    timeout: 15000
                }).done(function (response) {
                    if (response.success) {
                        $('#otp_email_hint').text('{{ __("gym::lang.otp_sent_to") ?? "OTP sent to" }}: ' + response.email_hint);
                    } else {
                        $('#otp_error').text(response.message).removeClass('hidden');
                    }
                }).fail(function () {
                    $('#otp_error').text('{{ __("gym::lang.failed_to_send_otp") ?? "Failed to send OTP" }}').removeClass('hidden');
                }).always(function () {
                    setLoading(false);
                });
            }

            function verifyOtp() {
                const otp = $('#otp_input').val().trim();
                const memberId = $('#otp_member_id').val();

                if (otp.length !== 6) {
                    $('#otp_error').text('{{ __("gym::lang.enter_6_digit_otp") ?? "Please enter 6-digit OTP code" }}').removeClass('hidden');
                    return;
                }

                setLoading(true);
                $('#otp_error').addClass('hidden');

                $.ajax({
                    url: "{{ route('gym.public_checkin_otp') }}",
                    method: 'POST',
                    data: {
                        member_id: memberId,
                        otp: otp,
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    timeout: 10000
                }).done(function (response) {
                    if (response.success) {
                        $('#otp_container').addClass('hidden');
                        showResult(response);
                    } else {
                        $('#otp_error').text(response.message).removeClass('hidden');
                        playBeep(false);
                    }
                }).fail(function () {
                    $('#otp_error').text('{{ __("gym::lang.verification_failed") ?? "Verification failed" }}').removeClass('hidden');
                    playBeep(false);
                }).always(function () {
                    setLoading(false);
                });
            }

            // OTP Event Handlers
            $('#verify_otp_btn').on('click', function () {
                verifyOtp();
            });

            $('#otp_input').on('keypress', function (e) {
                if (e.which === 13) {
                    verifyOtp();
                }
            });

            $('#resend_otp_btn').on('click', function () {
                const memberId = $('#otp_member_id').val();
                sendOtp(memberId);
            });

            $('#cancel_otp_btn').on('click', function () {
                $('#otp_container').addClass('hidden');
                if (currentMode === 'camera') {
                    startScanner(currentCameraId);
                } else {
                    $('#usb_barcode_input').val('').focus();
                }
            });

            function onScanError(errorMessage) {
                // Silent - normal scanning errors
            }

            async function populateCameras() {
                try {
                    const devices = await Html5Qrcode.getCameras();
                    const select = $('#camera_selection');
                    select.empty();

                    if (devices && devices.length > 0) {
                        devices.forEach(function (device) {
                            select.append($('<option>', {
                                value: device.id,
                                text: device.label || ('Camera ' + (devices.indexOf(device) + 1))
                            }));
                        });

                        if (devices.length > 1) {
                            select.removeClass('hidden');
                        }

                        currentCameraId = devices[0].id;
                        console.log('Found ' + devices.length + ' camera(s)');
                    }
                } catch (e) {
                    console.warn('Cannot enumerate cameras:', e);
                }
            }

            // Event handlers
            $('#start_button').on('click', async function () {
                if (!currentCameraId) {
                    await populateCameras();
                }
                const selectedCamera = $('#camera_selection').val() || currentCameraId;
                startScanner(selectedCamera);
            });

            $('#stop_button').on('click', function () {
                stopScanner();
            });

            $('#camera_selection').on('change', function () {
                const selectedId = $(this).val();
                currentCameraId = selectedId;
                if (isScanning) {
                    stopScanner().then(() => startScanner(selectedId));
                }
            });

            $('#scan_again_btn').on('click', function () {
                $('#result_container').addClass('hidden');
                if (currentMode === 'camera') {
                    startScanner(currentCameraId);
                } else {
                    // USB mode: just focus back to input and clear it
                    $('#usb_barcode_input').val('').focus();
                    updateUsbStatus('ready');
                }
            });

            // ==========================================
            // USB BARCODE SCANNER MODE
            // ==========================================
            let currentMode = 'camera'; // 'camera' or 'usb'
            let lastInputTime = 0;
            let inputBuffer = '';
            let inputTimeout = null;

            function updateUsbStatus(status) {
                const statusEl = $('#usb_scanner_status');
                const areaEl = $('#usb_scanner_area');

                statusEl.removeClass('ready waiting');
                areaEl.removeClass('active scanning');

                if (status === 'ready') {
                    statusEl.addClass('ready');
                    areaEl.addClass('active');
                    statusEl.html('<i class="fa fa-circle"></i><span>{{ __("gym::lang.ready_to_scan") ?? "Ready to scan" }}</span>');
                } else if (status === 'scanning') {
                    statusEl.addClass('waiting');
                    areaEl.addClass('scanning');
                    statusEl.html('<i class="fa fa-circle"></i><span>{{ __("gym::lang.scanning") ?? "Scanning..." }}</span>');
                }
            }

            function switchMode(mode) {
                currentMode = mode;

                // Update toggle buttons
                $('.mode-toggle-btn').removeClass('active');
                $(`[data-mode="${mode}"]`).addClass('active');

                // Hide result container when switching modes
                $('#result_container').addClass('hidden');

                if (mode === 'camera') {
                    // Switch to camera mode
                    $('#camera_scanner_container').removeClass('hidden');
                    $('#usb_scanner_container').addClass('hidden');

                    // Auto-start camera if we have a camera ID
                    if (currentCameraId && !isScanning) {
                        setTimeout(() => {
                            startScanner(currentCameraId);
                        }, 300);
                    }
                } else {
                    // Switch to USB mode
                    $('#camera_scanner_container').addClass('hidden');
                    $('#usb_scanner_container').removeClass('hidden');

                    // Stop camera scanner if running
                    if (isScanning) {
                        stopScanner();
                    }

                    // Focus on USB input
                    setTimeout(() => {
                        $('#usb_barcode_input').val('').focus();
                        updateUsbStatus('ready');
                    }, 100);
                }

                // Save preference to localStorage
                localStorage.setItem('scannerMode', mode);
                console.log('Switched to mode:', mode);
            }

            // Mode toggle button handlers
            $('#mode_camera_btn').on('click', function () {
                switchMode('camera');
            });

            $('#mode_usb_btn').on('click', function () {
                switchMode('usb');
            });

            // USB Barcode Input Handler
            // USB scanners typically input characters very fast and end with Enter
            $('#usb_barcode_input').on('keydown', function (e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();

                    const barcodeValue = $(this).val().trim();
                    if (barcodeValue.length > 0 && !scanProcessing) {
                        processUsbBarcode(barcodeValue);
                    }
                    return false;
                }
            });

            // Also listen for rapid input (characteristic of barcode scanners)
            $('#usb_barcode_input').on('input', function () {
                const currentTime = Date.now();
                const inputValue = $(this).val();

                // If input is coming in very fast (< 50ms between chars), it's likely a scanner
                if (currentTime - lastInputTime < 50) {
                    updateUsbStatus('scanning');
                }
                lastInputTime = currentTime;

                // Clear any existing timeout
                if (inputTimeout) {
                    clearTimeout(inputTimeout);
                }

                // Set a timeout: if no more input comes in 100ms and we have data, 
                // it might be a scanner that doesn't send Enter
                inputTimeout = setTimeout(() => {
                    // Most USB scanners send Enter, but this is a fallback
                    // Only auto-submit if we have a reasonable length barcode
                    if (inputValue.length >= 5) {
                        updateUsbStatus('ready');
                    }
                }, 150);
            });

            // Keep focus on USB input when in USB mode
            $('#usb_barcode_input').on('blur', function () {
                if (currentMode === 'usb' && !scanProcessing) {
                    // Re-focus after a small delay (allows clicking other UI elements)
                    setTimeout(() => {
                        if (currentMode === 'usb' && !scanProcessing) {
                            $(this).focus();
                        }
                    }, 200);
                }
            });

            function processUsbBarcode(barcodeValue) {
                if (scanProcessing) {
                    console.log('Already processing a scan');
                    return;
                }

                scanProcessing = true;
                console.log('USB Barcode scanned:', barcodeValue);

                // Play beep immediately
                playBeep(true);

                // Update status
                updateUsbStatus('scanning');
                setLoading(true);

                // Clear input
                $('#usb_barcode_input').val('');

                $.ajax({
                    url: "{{ route('gym.public_checkinout') }}",
                    method: 'POST',
                    data: {
                        member_id: barcodeValue,
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    timeout: 10000
                }).done(function (response) {
                    console.log('Response:', response);

                    // Handle OTP required for check-in
                    if (response.success && response.action === 'require_otp') {
                        showOtpForm(response);
                        // Auto-send OTP
                        sendOtp(response.member_id);
                    } else {
                        showResult(response);
                    }
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    showResult({
                        success: false,
                        message: '{{ __("gym::lang.error_processing") ?? "Error processing request" }}'
                    });
                }).always(function () {
                    setLoading(false);
                    scanProcessing = false;
                    updateUsbStatus('ready');

                    // Re-focus input for next scan after showing result
                    setTimeout(() => {
                        if (currentMode === 'usb') {
                            $('#usb_barcode_input').focus();
                        }
                    }, 500);
                });
            }

            // Initialize
            (async function init() {
                console.log('Initializing scanner...');

                // Check for saved mode preference
                const savedMode = localStorage.getItem('scannerMode');

                if (savedMode === 'usb') {
                    // Start in USB mode
                    switchMode('usb');
                } else {
                    // Default: Camera mode
                    await populateCameras();
                    // Auto-start on page load
                    if (currentCameraId) {
                        setTimeout(() => {
                            startScanner(currentCameraId);
                        }, 500);
                    }
                }
            })();
        });
    </script>
@endsection