<?php

namespace App\Http\Controllers;

use App\Models\DamagedMedicine;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DamagedMedicineController extends Controller
{
    /**
     * عرض قائمة الأدوية التالفة مع إمكانيات متقدمة للفلترة
     */
    public function index(Request $request)
    {
        try {
            // التحقق من صحة البيانات المدخلة
            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'reason' => 'nullable|in:expired,damaged,storage_issue',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'medicine_batch_id' => 'nullable|exists:medicine_batches,id'
            ]);

            // بناء الاستعلام الأساسي
            $query = DamagedMedicine::with(['batch.medicine' => function($q) {
                $q->select('id', 'medicine_name', 'sentific_name', 'arabic_name', 'bar_code');
            }]);

            // تطبيق الفلاتر
            $this->applyFilters($query, $validated);

            // تطبيق الترتيب الافتراضي
            $query->orderBy('damaged_at', 'desc');

            // الحصول على النتائج
            $damagedMedicines = $query->paginate(15);

            // تنسيق البيانات
            $damagedMedicines->getCollection()->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'medicine_name' => $item->batch->medicine->medicine_name ?? null,
                    'barcode' => $item->batch->medicine->bar_code ?? null,
                    'batch_number' => $item->batch->batch_number ?? null,
                    'batch_expiry_date' => $item->batch->expiry_date,
                    'quantity_damaged' => $item->quantity_talif,
                    'reason' => $item->reason,
                    'reason_text' => $this->getReasonText($item->reason),
                    'unit_price' => $item->batch->unit_price ?? 0,
                    'total_loss' => ($item->quantity_talif * ($item->batch->unit_price ?? 0)),
                    'damaged_at' => $item->damaged_at,
                    'notes' => $item->notes
                ];
            });

            // حساب الإحصائيات الأساسية
            $totalDamaged = $query->sum('quantity_talif');

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'تم جلب الأدوية التالفة بنجاح',
                'data' => [
                    'damaged_medicines' => $damagedMedicines->items(),
                    'total_damaged_quantity' => $totalDamaged
                ],
                'meta' => [
                    'current_page' => $damagedMedicines->currentPage(),
                    'last_page' => $damagedMedicines->lastPage(),
                    'total' => $damagedMedicines->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تطبيق الفلاتر على الاستعلام
     */
    private function applyFilters($query, array $filters)
    {


        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('batch.medicine', function($q) use ($search) {
                $q->where('batch_number',"{$search}")
                  ->orWhere('medicine_name', 'like', "%{$search}%")
                  ->orWhere('sentific_name', 'like', "%{$search}%")
                  ->orWhere('arabic_name', 'like', "%{$search}%")
                  ->orWhere('bar_code', 'like', "{$search}");
            });
        }

        // فلتر السبب
        if (!empty($filters['reason'])) {
            $query->where('reason', $filters['reason']);
        }

        // فلتر التاريخ
        if (!empty($filters['date_from'])) {
            $query->whereDate('damaged_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('damaged_at', '<=', $filters['date_to']);
        }

        // فلتر الدواء
        if (!empty($filters['medicine_batch_id'])) {
            $query->where('medicine_batch_id', $filters['medicine_batch_id']);
        }
    }

    /**
     * الحصول على نص السبب
     */
    private function getReasonText($reason)
    {
        return match($reason) {
            'expired' => 'منتهي الصلاحية',
            'damaged' => 'تالف',
            'storage_issue' => 'مشكلة في التخزين',
            default => 'غير محدد'
        };
    }

    /**
     * البحث عن الدواء باستخدام الباركود
     */
    public function searchByBarcode(Request $request)
    {
        $request->validate([
            'bar_code' => 'required|string'
        ]);

        $medicine = Medicine::where('bar_code', $request->query('bar_code'))
                          ->where('quantity', '>', 0)
                          ->first();

        if (!$medicine) {
            return response()->json([
                'status' => 'error',
                'message' => 'لم يتم العثور على الدواء أو أنه غير متوفر في المخزون',
                'status_code' => 404
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'medicine' => $medicine,
            'status_code' => 200,
            'message' => 'تم العثور على الدواء بنجاح'
        ]);
    }

    /**
     * حفظ دواء تالف جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'batch_id' => 'required|exists:medicine_batches,id',
            'quantity_talif' => 'required|integer|min:1',
            'reason' => 'required|in:expired,damaged,storage_issue',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $medicine_batch = MedicineBatch::findOrFail($request->batch_id);

            // التحقق من أن الكمية المتوفرة كافية
            if ($medicine_batch->quantity < $request->quantity_talif) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'الكمية المطلوبة غير متوفرة في المخزون',
                    'status_code' => 400
                ], 400);
            }

            // إنشاء سجل الدواء التالف
            $damagedMedicine = DamagedMedicine::create([
                'medicine_batch_id' => $request->batch_id,
                'quantity_talif' => $request->quantity_talif,
                'reason' => $request->reason,
                'notes' => $request->notes
            ]);

            // تحديث كمية الدواء في المخزون
            $medicine_batch->decrement('quantity', $request->quantity_talif);
            $medicine_batch->medicine->decrement('quantity', $request->quantity_talif);

            if ($medicine_batch->quantity == 0) {
                $medicine_batch->is_active = false;
                $medicine_batch->save();
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'تم تسجيل الدواء التالف بنجاح',
                // 'data' => $damagedMedicine->load('medicine'),
                'data' => $damagedMedicine->load(['batch', 'batch.medicine']),
                'status_code' => 201
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء تسجيل الدواء التالف',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }
}



