@extends('layouts.master')
@section('title')
    الفواتير المدفوعة جزئياً
@stop
@section('css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    body { background: #f6f8fa; }
    .partially-alert {
        background: linear-gradient(90deg, #36d1dc 0%, #ffaf7b 100%);
        color: #4e342e;
        border-radius: 14px;
        padding: 18px 24px;
        margin-bottom: 32px;
        font-size: 1.15em;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(54,209,220,0.08);
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .all-invoices-card {
        background: linear-gradient(120deg, #f8fafc 60%, #e8f4fd 100%);
        border-radius: 18px;
        box-shadow: 0 8px 32px 0 rgba(80, 112, 255, 0.13), 0 2px 8px 0 rgba(80, 112, 255, 0.10);
        margin-bottom: 36px;
        padding: 32px 32px 22px 32px;
        transition: box-shadow 0.3s, transform 0.2s;
        border: none;
        position: relative;
        border-left: 5px solid #36d1dc;
    }
    .all-invoices-card:hover {
        box-shadow: 0 16px 48px 0 rgba(80, 112, 255, 0.18), 0 4px 16px 0 rgba(80, 112, 255, 0.13);
        transform: translateY(-4px) scale(1.01);
    }
    .all-invoices-card .invoice-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
    }
    .all-invoices-card .invoice-header .icon {
        font-size: 2.5em;
        color: #36d1dc;
        background: #e0f7fa;
        border-radius: 50%;
        padding: 14px 18px;
        box-shadow: 0 2px 8px rgba(54,209,220,0.10);
        margin-bottom: 6px;
    }
    .all-invoices-card .invoice-header .invoice-title {
        font-size: 1.35em;
        font-weight: 800;
        color: #2c3e50;
        letter-spacing: 1px;
        text-align: center;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .all-invoices-card .invoice-header .status-badge {
        margin-top: 8px;
    }
    .badge-partially {
        background: linear-gradient(90deg, #36d1dc 0%, #ffaf7b 100%);
        color: #fff;
        font-size: 1em;
        padding: 8px 18px;
        border-radius: 16px;
        box-shadow: 0 1px 4px rgba(54,209,220,0.08);
        font-weight: 600;
        letter-spacing: 1px;
    }
    .invoice-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-bottom: 18px;
        font-size: 1.08em;
    }
    .all-invoices-card .invoice-info div {
        color: #444;
        padding: 8px 0;
    }
    .invoice-details {
        background: rgba(255,255,255,0.8);
        border-radius: 12px;
        padding: 18px;
        margin-top: 10px;
        box-shadow: 0 2px 8px rgba(80,112,255,0.06);
    }
    .invoice-details h6 {
        color: #2c3e50;
        margin-bottom: 12px;
        font-weight: 700;
        border-bottom: 2px solid #36d1dc;
        padding-bottom: 8px;
        font-size: 1.08em;
    }
    .table th, .table td { 
        vertical-align: middle; 
        text-align: center; 
    }
    .table th {
        background: #f8f9fa;
        color: #2c3e50;
        font-weight: 600;
    }
    .actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 10px;
    }
    .btn-outline-primary {
        border-color: #36d1dc;
        color: #36d1dc;
        font-weight: 600;
        background: #f8f9fa;
        transition: all 0.2s;
    }
    .btn-outline-primary:hover {
        background: #36d1dc;
        color: #fff;
        border-color: #36d1dc;
        box-shadow: 0 2px 8px rgba(54,209,220,0.13);
    }
    .btn-outline-info {
        border-color: #ffaf7b;
        color: #ffaf7b;
        font-weight: 600;
        background: #f8f9fa;
        transition: all 0.3s ease;
    }
    .btn-outline-info:hover {
        background: #28a745 !important;
        color: #fff !important;
        border-color: #28a745 !important;
        box-shadow: 0 4px 12px rgba(40,167,69,0.25) !important;
        transform: translateY(-2px);
    }
    /* تأكيد إضافي لزر العرض */
    .actions .btn-outline-info:hover {
        background: #28a745 !important;
        color: #fff !important;
        border-color: #28a745 !important;
        box-shadow: 0 4px 12px rgba(40,167,69,0.25) !important;
        transform: translateY(-2px);
    }
    @media (max-width: 600px) {
        .all-invoices-card {
            padding: 16px 6px 12px 6px;
        }
        .invoice-details {
            padding: 8px;
        }
        .invoice-header .invoice-title {
            font-size: 1em;
        }
    }
</style>
@endsection
@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">الفواتير</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ الفواتير المدفوعة جزئياً</span>
            </div>
        </div>
    </div>
@endsection
@section('content')
<div class="row">
    <div class="col-xl-12">
        @if($invoices->count() > 0)
        <div class="partially-alert">
            <i class="fas fa-hourglass-half fa-lg"></i>
            <span>تنويه: هذه الفواتير تم دفع جزء منها فقط. يرجى متابعة صيدلية الهدى لاستكمال الدفع قبل تاريخ الاستحقاق.</span>
        </div>
        @endif
        @if($invoices->count() == 0)
            <div class="text-center py-5">
                <i class="fas fa-file-invoice" style="font-size: 4em; color: #36d1dc;"></i>
                <h4 class="mt-3 text-muted">لا توجد فواتير مدفوعة جزئياً</h4>
                <p class="text-muted">جميع الفواتير إما مدفوعة بالكامل أو غير مدفوعة</p>
            </div>
        @else
            @foreach($invoices as $invoice)
                <div class="all-invoices-card">
                    <div class="invoice-header">
                        <span class="icon"><i class="fas fa-file-invoice-dollar"></i></span>
                        <span class="invoice-title"><i class="fas fa-hashtag ml-1"></i> فاتورة رقم {{ $invoice->invoice_number }}</span>
                        <span class="badge badge-partially status-badge">مدفوعة جزئياً</span>
                    </div>
                    <div class="invoice-info">
                        <div><i class="fas fa-hashtag ml-1"></i> <strong>رقم الطلبية:</strong> {{ $invoice->order->order_number ?? '-' }}</div>
                        <div><i class="fas fa-user ml-1"></i> <strong>المورد:</strong> {{ $invoice->order->supplier->contact_person_name ?? '-' }}</div>
                        <div><i class="fas fa-calendar-alt ml-1"></i> <strong>تاريخ الفاتورة:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('Y/m/d') }}</div>
                        <div><i class="fas fa-calendar-check ml-1"></i> <strong>تاريخ الاستحقاق:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('Y/m/d') }}</div>
                        <div><i class="fas fa-money-bill ml-1"></i> <strong>الإجمالي:</strong> {{ number_format($invoice->total_amount, 2) }} ليرة سوري</div>
                    </div>
                    <div class="invoice-details">
                        <h6><i class="fas fa-list-ul ml-1"></i> ملخص الطلبية</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>المنتج</th>
                                        <th>الكمية</th>
                                        <th>سعر الوحدة</th>
                                        <th>الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->order->orderItems as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $item->medicine->medicine_name ?? 'غير محدد' }}</td>
                                            <td><span class="badge badge-secondary">{{ $item->quantity }}</span></td>
                                            <td>{{ number_format($item->unit_price, 2) }} ل.س</td>
                                            <td>{{ number_format($item->total_price, 2) }} ل.س</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="actions">
                            <a href="{{ route('invoices.download', $invoice->id) }}" class="btn btn-sm btn-outline-primary" title="تحميل الفاتورة"><i class="fas fa-download"></i> تحميل</a>
                            <a href="{{ route('supplier.invoices.show-pdf', $invoice->id) }}" class="btn btn-sm btn-outline-info" title="عرض الفاتورة" target="_blank"><i class="fas fa-eye"></i> عرض</a>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
        <div class="d-flex justify-content-center mt-3">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection
