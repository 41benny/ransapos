<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\BackdateSalesImport;
use App\Models\Customer;
use App\Models\Outlet;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Services\BackdateSaleService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BackdateSaleController extends Controller
{
    public function __construct(private BackdateSaleService $backdateSaleService)
    {
    }

    public function index(): View
    {
        return view('admin.backdate-sales.index', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        try {
            $sale = $this->backdateSaleService->createBackdateSale($validated, $request->user());

            return redirect()
                ->route('admin.backdate-sales.index')
                ->with('success', 'Penjualan backdate berhasil disimpan. Invoice: ' . $sale->invoice_number);
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan backdate: ' . $e->getMessage());
        }
    }

    public function previewImport(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        try {
            $import = new BackdateSalesImport();
            Excel::import($import, $request->file('file'));
            $preview = $this->buildImportPreview($import->rows());

            session(['backdate_sales_import_payload' => $preview['payloads']]);

            return view('admin.backdate-sales.import-preview', array_merge($this->formData(), [
                'preview' => $preview,
            ]));
        } catch (Exception $e) {
            return back()->with('error', 'Gagal membaca file import: ' . $e->getMessage());
        }
    }

    public function processImport(Request $request): RedirectResponse
    {
        $payloads = session('backdate_sales_import_payload', []);

        if (empty($payloads)) {
            return redirect()
                ->route('admin.backdate-sales.index')
                ->with('error', 'Tidak ada data import yang siap diproses. Upload dan preview file kembali.');
        }

        try {
            $sales = $this->backdateSaleService->createMany($payloads, $request->user());
            session()->forget('backdate_sales_import_payload');

            return redirect()
                ->route('admin.backdate-sales.index')
                ->with('success', count($sales) . ' transaksi backdate berhasil diimport.');
        } catch (Exception $e) {
            return redirect()
                ->route('admin.backdate-sales.index')
                ->with('error', 'Gagal proses import: ' . $e->getMessage());
        }
    }

    public function template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Backdate Sales');

        $headers = [
            'kode_transaksi_manual',
            'tanggal_penjualan',
            'kode_outlet',
            'sku_produk',
            'nama_produk',
            'qty',
            'harga_satuan',
            'diskon_item',
            'metode_pembayaran',
            'jumlah_bayar',
            'nama_customer',
            'catatan_item',
            'catatan_transaksi',
            'referensi_pembayaran',
            'alasan_backdate',
        ];

        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValueExplicit($column . '1', $header, DataType::TYPE_STRING);
        }

        $example = [
            'MBK2-20260503-001',
            '2026-05-03',
            'MBK2',
            'SKU-001',
            'Nama produk contoh',
            1,
            25000,
            0,
            'CASH',
            25000,
            'Walk-in',
            '',
            'Input dari catatan manual outlet',
            '',
            'Gangguan sistem outlet',
        ];

        foreach ($example as $index => $value) {
            $column = chr(65 + $index);
            $sheet->setCellValueExplicit($column . '2', (string) $value, DataType::TYPE_STRING);
        }

        foreach (range('A', 'O') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'backdate_sales_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return response()->download($tempFile, 'template-import-penjualan-backdate.xlsx')->deleteFileAfterSend(true);
    }

    private function formData(): array
    {
        return [
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'products' => $this->backdateSaleService->productOptions(),
            'paymentMethods' => PaymentMethod::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->limit(200)->get(['id', 'name', 'customer_code']),
            'maxBackdateDays' => $this->backdateSaleService->maxBackdateDays(),
            'defaultSaleDate' => today()->toDateString(),
        ];
    }

    private function rules(): array
    {
        return [
            'manual_reference' => ['required', 'string', 'max:100'],
            'sale_date' => ['required', 'date'],
            'outlet_id' => ['required', 'exists:outlets,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:200'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string'],
            'backdate_reason' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function messages(): array
    {
        return [
            'manual_reference.required' => 'Kode transaksi manual wajib diisi.',
            'backdate_reason.required' => 'Alasan backdate wajib diisi.',
            'items.required' => 'Minimal harus ada 1 item produk.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
        ];
    }

    private function buildImportPreview(array $rows): array
    {
        $errors = [];
        $groups = [];
        $payloads = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $manualRef = strtoupper(trim((string) ($row['kode_transaksi_manual'] ?? '')));

            if ($manualRef === '') {
                $errors[] = "Baris {$rowNumber}: kode_transaksi_manual wajib diisi.";
                continue;
            }

            $groups[$manualRef][] = ['row_number' => $rowNumber, 'row' => $row];
        }

        foreach ($groups as $manualRef => $items) {
            $first = $items[0]['row'];
            $groupErrors = [];

            if (Sale::query()->where('manual_reference', $manualRef)->where('is_backdated', true)->exists()) {
                $groupErrors[] = "Kode {$manualRef} sudah pernah diimport.";
            }

            $saleDate = $this->parseDate($first['tanggal_penjualan'] ?? null);
            if (!$saleDate) {
                $groupErrors[] = "Kode {$manualRef}: tanggal_penjualan tidak valid.";
            } else {
                try {
                    $saleDate = $this->backdateSaleService->validateSaleDate($saleDate);
                } catch (Exception $e) {
                    $groupErrors[] = "Kode {$manualRef}: " . $e->getMessage();
                }
            }

            $outlet = $this->findOutlet($first['kode_outlet'] ?? null);
            if (!$outlet) {
                $groupErrors[] = "Kode {$manualRef}: kode_outlet tidak ditemukan.";
            }

            $paymentMethod = $this->findPaymentMethod($first['metode_pembayaran'] ?? null);
            if (!$paymentMethod) {
                $groupErrors[] = "Kode {$manualRef}: metode_pembayaran tidak ditemukan.";
            }

            $reason = trim((string) ($first['alasan_backdate'] ?? ''));
            if ($reason === '') {
                $groupErrors[] = "Kode {$manualRef}: alasan_backdate wajib diisi.";
            }

            $headerKeys = ['tanggal_penjualan', 'kode_outlet', 'metode_pembayaran', 'jumlah_bayar', 'nama_customer', 'catatan_transaksi', 'referensi_pembayaran', 'alasan_backdate'];
            foreach ($items as $item) {
                foreach ($headerKeys as $key) {
                    if (trim((string) ($item['row'][$key] ?? '')) !== trim((string) ($first[$key] ?? ''))) {
                        $groupErrors[] = "Baris {$item['row_number']}: {$key} harus sama untuk kode {$manualRef}.";
                    }
                }
            }

            $payloadItems = [];
            $itemTotal = 0.0;
            foreach ($items as $item) {
                $row = $item['row'];
                $product = $this->findProduct($row['sku_produk'] ?? null);
                $qty = (float) ($row['qty'] ?? 0);
                $price = (float) ($row['harga_satuan'] ?? 0);
                $discount = (float) ($row['diskon_item'] ?? 0);

                if (!$product) {
                    $groupErrors[] = "Baris {$item['row_number']}: sku_produk tidak ditemukan.";
                }
                if ($qty <= 0) {
                    $groupErrors[] = "Baris {$item['row_number']}: qty harus lebih dari 0.";
                }
                if ($price < 0 || $discount < 0) {
                    $groupErrors[] = "Baris {$item['row_number']}: harga_satuan dan diskon_item tidak boleh minus.";
                }

                if ($product && $qty > 0) {
                    $payloadItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'discount_amount' => $discount,
                        'notes' => $row['catatan_item'] ?? null,
                    ];
                    $itemTotal += max(0, ($qty * $price) - $discount);
                }
            }

            if (empty($groupErrors) && $outlet && $paymentMethod && $saleDate) {
                $payloads[] = [
                    'manual_reference' => $manualRef,
                    'sale_date' => $saleDate,
                    'outlet_id' => $outlet->id,
                    'payment_method_id' => $paymentMethod->id,
                    'payment_amount' => (float) ($first['jumlah_bayar'] ?? 0),
                    'payment_reference' => $first['referensi_pembayaran'] ?? null,
                    'customer_name' => $first['nama_customer'] ?? null,
                    'notes' => $first['catatan_transaksi'] ?? null,
                    'backdate_reason' => $reason,
                    'items' => $payloadItems,
                ];
            }

            foreach ($groupErrors as $error) {
                $errors[] = $error;
            }

            $groups[$manualRef] = [
                'manual_reference' => $manualRef,
                'sale_date' => $saleDate,
                'outlet' => $outlet?->name,
                'payment_method' => $paymentMethod?->name,
                'item_count' => count($items),
                'total' => $itemTotal,
                'errors' => $groupErrors,
            ];
        }

        if (!empty($errors)) {
            $payloads = [];
            session()->forget('backdate_sales_import_payload');
        }

        return [
            'groups' => array_values($groups),
            'errors' => $errors,
            'payloads' => $payloads,
            'transaction_count' => count($payloads),
            'item_count' => array_sum(array_map(fn ($group) => (int) ($group['item_count'] ?? 0), $groups)),
            'total' => array_sum(array_map(fn ($group) => (float) ($group['total'] ?? 0), $groups)),
        ];
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Exception) {
            return null;
        }
    }

    private function findOutlet(mixed $value): ?Outlet
    {
        $code = trim((string) $value);
        if ($code === '') {
            return null;
        }

        return Outlet::query()
            ->where('code', $code)
            ->orWhere('id', $code)
            ->first();
    }

    private function findPaymentMethod(mixed $value): ?PaymentMethod
    {
        $needle = trim((string) $value);
        if ($needle === '') {
            return null;
        }

        return PaymentMethod::query()
            ->where('code', $needle)
            ->orWhere('name', $needle)
            ->first();
    }

    private function findProduct(mixed $value): ?Product
    {
        $sku = trim((string) $value);
        if ($sku === '') {
            return null;
        }

        return Product::query()->where('sku', $sku)->first();
    }
}
