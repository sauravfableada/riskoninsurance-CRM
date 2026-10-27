<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Voucher - {{ $booking->booking_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8f9fa; }
        .voucher-card { max-width: 800px; margin: 40px auto; background: #fff; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .header-logo { height: 50px; }
        .section-title { font-size: 0.8rem; font-weight: 800; color: #6c757d; text-uppercase; border-bottom: 2px solid #f1f1f1; padding-bottom: 5px; margin-bottom: 15px; letter-spacing: 1px; }
        .day-box { border-left: 3px solid #0d6efd; padding-left: 15px; margin-bottom: 20px; }
        @media print {
            body { background: #fff; }
            .voucher-card { box-shadow: none; margin: 0; padding: 20px; max-width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="div no-print text-center mt-4">
    <button onclick="window.print()" class="btn btn-dark-blue px-4 rounded-pill">Print Voucher</button>
    <a href="{{ route('bookings.show', $booking) }}" class="btn btn-link">Back to Booking</a>
</div>

<div class="voucher-card">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-primary mb-0">TRAVEL VOUCHER</h2>
            <p class="text-muted small">CONFIRMED BOOKING #{{ $booking->booking_no }}</p>
        </div>
        <div class="text-end">
            <h5 class="fw-bold mb-0">Travel CRM</h5>
            <p class="small text-muted mb-0">inventory-crm.test</p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-6">
            <div class="section-title">Customer Info</div>
            <h6 class="fw-bold mb-1">{{ $booking->customer?->name }}</h6>
            <p class="small text-muted mb-0">{{ $booking->customer?->email }}<br>{{ $booking->customer?->phone }}</p>
        </div>
        <div class="col-6 text-end">
            <div class="section-title">Booking Details</div>
            <p class="small mb-1 fw-bold">{{ $booking->tourPackage?->name }}</p>
            <p class="small text-muted mb-0">{{ $booking->travel_start_date }} to {{ $booking->travel_end_date }}</p>
            <p class="small text-muted">{{ $booking->adults }} Adults, {{ $booking->children }} Children</p>
        </div>
    </div>

    <div class="mb-5">
        <div class="section-title">Travelers</div>
        <div class="table-responsive">
            <table class="table table-sm small">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Passenger Name</th>
                        <th>Age</th>
                        <th>Passport #</th>
                        <th>Nationality</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($booking->passengers as $index => $pax)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-bold">{{ $pax->first_name }} {{ $pax->last_name }}</td>
                            <td>{{ $pax->age }}</td>
                            <td>{{ $pax->passport_no }}</td>
                            <td>{{ $pax->nationality }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-5">
        <div class="section-title">Detailed Itinerary</div>
        @foreach($booking->itinerary?->days ?? [] as $day)
            <div class="day-box">
                <h6 class="fw-bold mb-1">Day {{ $day->day_number }}: {{ $day->title }}</h6>
                <p class="small text-muted mb-2">{{ $day->description }}</p>
                <div class="d-flex gap-2">
                    @if($day->meals)
                        <span class="badge bg-light text-dark border small fw-normal">🍽️ {{ $day->meals }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-5 pt-4 border-top text-center">
        <p class="small text-muted mb-0">Thank you for choosing Travel CRM. Have a safe journey!</p>
        <p class="x-small text-muted" style="font-size: 0.7rem;">This is an electronically generated document. No signature required.</p>
    </div>
</div>

</body>
</html>
