<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SharedDatabase;
use App\Services\SharedDatabaseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SharedDatabaseController extends Controller
{
    public function __construct(private SharedDatabaseService $service) {}

    public function index()
    {
        $sharedDbs = SharedDatabase::with('creator')->orderBy('display_name')->get();
        return view('admin.shared-databases.index', compact('sharedDbs'));
    }

    public function create()
    {
        return view('admin.shared-databases.form', ['sharedDb' => null]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'slug'         => ['required', 'regex:/^[a-z0-9_]{2,32}$/', 'unique:shared_databases,slug'],
            'display_name' => ['required', 'string', 'max:120'],
            'description'  => ['nullable', 'string', 'max:500'],
            'permission'   => ['required', Rule::in(['none', 'readonly', 'readwrite'])],
            'sql_file'     => ['required', 'file', 'mimes:sql,txt', 'max:20480'],
        ]);

        try {
            $this->service->create(
                $request->input('slug'),
                $request->input('display_name'),
                $request->input('description'),
                $request->input('permission'),
                $request->file('sql_file')
            );

            return redirect()->route('admin.shared-databases.index')
                ->with('success', "Database '{$request->input('display_name')}' đã được tạo thành công.");
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Lỗi khi tạo database: ' . $e->getMessage());
        }
    }

    public function show(SharedDatabase $sharedDatabase)
    {
        $sharedDatabase->load('creator');
        return view('admin.shared-databases.show', compact('sharedDatabase'));
    }

    public function edit(SharedDatabase $sharedDatabase)
    {
        return view('admin.shared-databases.form', ['sharedDb' => $sharedDatabase]);
    }

    public function update(Request $request, SharedDatabase $sharedDatabase)
    {
        $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'description'  => ['nullable', 'string', 'max:500'],
            'permission'   => ['required', Rule::in(['none', 'readonly', 'readwrite'])],
            'sql_file'     => ['nullable', 'file', 'mimes:sql,txt', 'max:20480'],
        ]);

        try {
            $sharedDatabase->update([
                'display_name' => $request->input('display_name'),
                'description'  => $request->input('description'),
                'permission'   => $request->input('permission'),
            ]);

            if ($request->hasFile('sql_file')) {
                $this->service->reimport($sharedDatabase, $request->file('sql_file'));
            }

            return redirect()->route('admin.shared-databases.index')
                ->with('success', "Database '{$sharedDatabase->display_name}' đã được cập nhật.");
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Lỗi khi cập nhật: ' . $e->getMessage());
        }
    }

    public function destroy(SharedDatabase $sharedDatabase)
    {
        try {
            $name = $sharedDatabase->display_name;
            $this->service->drop($sharedDatabase);
            return redirect()->route('admin.shared-databases.index')
                ->with('success', "Database '{$name}' đã bị xóa.");
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi xóa: ' . $e->getMessage());
        }
    }

    public function reimport(Request $request, SharedDatabase $sharedDatabase)
    {
        $request->validate([
            'sql_file' => ['required', 'file', 'mimes:sql,txt', 'max:20480'],
        ]);

        try {
            $this->service->reimport($sharedDatabase, $request->file('sql_file'));
            return redirect()->route('admin.shared-databases.show', $sharedDatabase)
                ->with('success', 'Dữ liệu đã được import lại thành công.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi import: ' . $e->getMessage());
        }
    }

    public function refresh(SharedDatabase $sharedDatabase)
    {
        $this->service->refreshTablesInfo($sharedDatabase);
        return redirect()->route('admin.shared-databases.show', $sharedDatabase)
            ->with('success', 'Thông tin bảng đã được cập nhật.');
    }
}
