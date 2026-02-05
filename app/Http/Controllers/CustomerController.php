<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        abort_if(!auth()->user()->can('customer_view'), 403);
        if ($request->ajax()) {
            $customers = Customer::query(); // Optimized: Server-side query
            return DataTables::of($customers)
                ->addIndexColumn()
                ->addColumn('name', fn($data) => $data->name)
                ->addColumn('phone', fn($data) => $data->phone)
                ->addColumn('address', fn($data) => $data->address)
                ->addColumn('created_at', fn($data) => $data->created_at->format('d M, Y')) // Using Carbon for formatting
                ->addColumn('action', function ($data) {
                    $actionHtml = '<div class="btn-group">
        <button type="button" class="btn bg-gradient-primary btn-flat">Action</button>
        <button type="button" class="btn bg-gradient-primary btn-flat dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">
            <span class="sr-only">Toggle Dropdown</span>
        </button>
        <div class="dropdown-menu" role="menu">';

                    // Check if the user has permission to update customers
                    if (auth()->user()->can('customer_update')) {
                        $actionHtml .= '<a class="dropdown-item" href="' . route('backend.admin.customers.edit', $data->id) . '" ' . ($data->id == 1 ? 'onclick="event.preventDefault();"' : '') . '>
            <i class="fas fa-edit"></i> Edit
        </a>';
                        $actionHtml .= '<div class="dropdown-divider"></div>';
                    }

                    // Check if the user has permission to delete customers
                    if (auth()->user()->can('customer_delete')) {
                        $actionHtml .= '<form action="' . route('backend.admin.customers.destroy', $data->id) . '" method="POST" style="display:inline;">
            ' . csrf_field() . '
            ' . method_field("DELETE") . '
            <button type="submit" ' . ($data->id == 1 ? 'disabled' : '') . ' class="dropdown-item" onclick="return confirm(\'Are you sure?\')">
                <i class="fas fa-trash"></i> Delete
            </button>
        </form>';
                        $actionHtml .= '<div class="dropdown-divider"></div>';
                    }

                    if (auth()->user()->can('customer_sales')) {
                        $actionHtml .= '<a class="dropdown-item" href="' . route('backend.admin.customers.orders', $data->id) . '">
        <i class="fas fa-cart-plus"></i> Sales
    </a>';
                    }

                    $actionHtml .= '</div></div>';
                    return $actionHtml;
                })

                ->rawColumns(['name', 'phone', 'address', 'created_at', 'action'])
                ->toJson();
        }


        return view('backend.customers.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        abort_if(!auth()->user()->can('customer_create'), 403);
        return view('backend.customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        abort_if(!auth()->user()->can('customer_create'), 403);

        // Pakistani validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^03\d{9}$/', 'unique:customers,phone'],
            'cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'address' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
        ];
        $messages = [
            'phone.regex' => 'Phone must be a valid Pakistani mobile number (03xxxxxxxxx).',
            'cnic.regex' => 'CNIC must be in format xxxxx-xxxxxxx-x.',
        ];

        if ($request->wantsJson()) {
            $request->validate($rules, $messages);

            $customer = Customer::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'cnic' => $request->cnic,
                'address' => $request->address,
                'credit_limit' => $request->credit_limit ?? 0,
            ]);

            return response()->json($customer);
        }
        $request->validate($rules, $messages);

        $customer = Customer::create($request->only(['name', 'phone', 'cnic', 'address', 'credit_limit']));

        session()->flash('success', 'Customer created successfully.');
        return to_route('backend.admin.customers.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {

        abort_if(!auth()->user()->can('customer_update'), 403);
        $customer = Customer::findOrFail($id);
        return view('backend.customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {

        abort_if(!auth()->user()->can('customer_update'), 403);
        $customer = Customer::findOrFail($id);

        // Pakistani validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^03\d{9}$/', 'unique:customers,phone,' . $customer->id],
            'cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'address' => 'nullable|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
        ];
        $messages = [
            'phone.regex' => 'Phone must be a valid Pakistani mobile number (03xxxxxxxxx).',
            'cnic.regex' => 'CNIC must be in format xxxxx-xxxxxxx-x.',
        ];

        $request->validate($rules, $messages);

        $customer->update($request->only(['name', 'phone', 'cnic', 'address', 'credit_limit']));

        session()->flash('success', 'Customer updated successfully.');
        return to_route('backend.admin.customers.index');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {

        abort_if(!auth()->user()->can('customer_delete'), 403);
        $customer = Customer::findOrFail($id);
        $customer->delete();
        session()->flash('success', 'Customer deleted successfully.');
        return to_route('backend.admin.customers.index');
    }
    public function getCustomers(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json(Customer::latest()->get());
        }
    }
    //get orders by customer id
    public function orders($id)
    {
        $customer = Customer::findOrFail($id);
        $orders = $customer->orders()->paginate(100);
        return view('backend.orders.index', compact('orders'));
    }
}
